<?php
declare(strict_types=1);

namespace App\Modules\Forums;

use App\Config\Database;
use App\Support\Json;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Module 7 — Forum & Discussion (Owner: Monika)
 *
 * ForumController — posts, comments and likes.
 *
 * Endpoints:
 *   GET    /api/forums                               (any logged-in)
 *   GET    /api/forums/{id}                          (any logged-in)
 *   POST   /api/forums                               (student)
 *   PUT    /api/forums/{id}                          (student — own only)
 *   DELETE /api/forums/{id}                          (student own OR admin)
 *   POST   /api/forums/{id}/like                     (any logged-in — toggle)
 *   POST   /api/forums/{id}/comments                 (student)
 *   DELETE /api/forums/{id}/comments/{comment_id}    (own comment OR admin)
 *
 * Deletion behaviour for admins:
 *   - If a post has comments  -> soft-delete (content cleared, row kept)
 *   - If a post has no comments -> hard-delete (row removed)
 *   Students can only delete their own posts (always soft-delete) and their own comments.
 */
final class ForumController
{
    // -----------------------------------------------------------------
    // POSTS
    // -----------------------------------------------------------------

    public function index(Request $request, Response $response): Response
    {
        $params  = $request->getQueryParams();
        $search  = trim((string) ($params['search'] ?? ''));
        $labelId = isset($params['label_id']) ? (int) $params['label_id'] : 0;

        $sql = "SELECT p.post_id, p.user_id, p.label_id, p.title, p.content,
                       p.likes, p.is_deleted, p.created_at,
                       u.full_name AS author_name,
                       l.name AS label_name,
                       (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.post_id) AS comment_count
                FROM posts p
                JOIN users u ON u.user_id = p.user_id
                LEFT JOIN labels l ON l.label_id = p.label_id
                WHERE 1=1";
        $args = [];

        if ($search !== '') {
            // Native PDO prepares require each placeholder used exactly once.
            $sql .= ' AND (p.title LIKE :qt OR p.content LIKE :qc)';
            $like = '%' . $search . '%';
            $args[':qt'] = $like;
            $args[':qc'] = $like;
        }
        if ($labelId > 0) {
            $sql .= ' AND p.label_id = :lid';
            $args[':lid'] = $labelId;
        }

        // Sort: ?sort=newest (default) or ?sort=popular
        $sort = (string) ($params['sort'] ?? 'newest');
        if ($sort === 'popular') {
            $sql .= ' ORDER BY p.likes DESC, p.created_at DESC';
        } else {
            $sql .= ' ORDER BY p.created_at DESC, p.likes DESC';
        }

        try {
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute($args);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        // Tell the caller which of these posts they've already liked.
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $likedSet = $this->fetchLikedSet($userId);

        foreach ($rows as &$r) {
            $r['liked_by_me'] = isset($likedSet[(int) $r['post_id']]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'data'    => $rows,
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid post id.',
            ]);
        }

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare(
                "SELECT p.post_id, p.user_id, p.label_id, p.title, p.content,
                        p.likes, p.is_deleted, p.created_at,
                        u.full_name AS author_name,
                        l.name AS label_name
                 FROM posts p
                 JOIN users u ON u.user_id = p.user_id
                 LEFT JOIN labels l ON l.label_id = p.label_id
                 WHERE p.post_id = :id LIMIT 1"
            );
            $stmt->execute([':id' => $id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Post not found.',
                ]);
            }

            $stmt = $pdo->prepare(
                "SELECT c.comment_id, c.post_id, c.user_id, c.content, c.created_at,
                        u.full_name AS author_name
                 FROM comments c
                 JOIN users u ON u.user_id = c.user_id
                 WHERE c.post_id = :id
                 ORDER BY c.created_at ASC"
            );
            $stmt->execute([':id' => $id]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $likedSet = $this->fetchLikedSet($userId, [$id]);
        $post['liked_by_me'] = isset($likedSet[$id]);

        return Json::write($response, 200, [
            'success' => true,
            'data'    => [
                'post'     => $post,
                'comments' => $comments,
            ],
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $body    = $this->readJson($request);

        $title    = trim((string) ($body['title'] ?? ''));
        $content  = trim((string) ($body['content'] ?? ''));
        $labelId  = isset($body['label_id']) && $body['label_id'] !== ''
            ? (int) $body['label_id']
            : null;

        $errors = [];
        if ($title === '' || mb_strlen($title) > 150) {
            $errors['title'] = 'Title is required (max 150 chars).';
        }
        if ($content === '') {
            $errors['content'] = 'Content is required.';
        } elseif (mb_strlen($content) > 5000) {
            $errors['content'] = 'Content must be 5000 characters or fewer.';
        }
        if (!empty($errors)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        try {
            $pdo = Database::getConnection();

            if ($labelId !== null) {
                $stmt = $pdo->prepare('SELECT label_id FROM labels WHERE label_id = :id LIMIT 1');
                $stmt->execute([':id' => $labelId]);
                if (!$stmt->fetch()) {
                    return Json::write($response, 400, [
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors'  => ['label_id' => 'Selected label does not exist.'],
                    ]);
                }
            }

            $stmt = $pdo->prepare(
                'INSERT INTO posts (user_id, label_id, title, content)
                 VALUES (:uid, :lid, :t, :c)'
            );
            $stmt->execute([
                ':uid' => $userId,
                ':lid' => $labelId,
                ':t'   => $title,
                ':c'   => $content,
            ]);
            $id = (int) $pdo->lastInsertId();

            $row = $this->fetchPostRow($pdo, $id);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 201, [
            'success' => true,
            'message' => 'Post created.',
            'data'    => $row,
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $body    = $this->readJson($request);

        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid post id.',
            ]);
        }

        $title   = trim((string) ($body['title'] ?? ''));
        $content = trim((string) ($body['content'] ?? ''));
        $labelId = isset($body['label_id']) && $body['label_id'] !== ''
            ? (int) $body['label_id']
            : null;

        $errors = [];
        if ($title === '' || mb_strlen($title) > 150) {
            $errors['title'] = 'Title is required (max 150 chars).';
        }
        if ($content === '') {
            $errors['content'] = 'Content is required.';
        } elseif (mb_strlen($content) > 5000) {
            $errors['content'] = 'Content must be 5000 characters or fewer.';
        }
        if (!empty($errors)) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare(
                'SELECT user_id, is_deleted FROM posts WHERE post_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Post not found.',
                ]);
            }
            if ((int) $row['is_deleted'] === 1) {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Cannot edit a deleted post.',
                ]);
            }
            if ((int) $row['user_id'] !== $userId) {
                return Json::write($response, 403, [
                    'success' => false,
                    'message' => 'You can only edit your own posts.',
                ]);
            }

            if ($labelId !== null) {
                $stmt = $pdo->prepare('SELECT label_id FROM labels WHERE label_id = :id LIMIT 1');
                $stmt->execute([':id' => $labelId]);
                if (!$stmt->fetch()) {
                    return Json::write($response, 400, [
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors'  => ['label_id' => 'Selected label does not exist.'],
                    ]);
                }
            }

            $stmt = $pdo->prepare(
                'UPDATE posts
                 SET title = :t, content = :c, label_id = :lid
                 WHERE post_id = :id'
            );
            $stmt->execute([
                ':t'   => $title,
                ':c'   => $content,
                ':lid' => $labelId,
                ':id'  => $id,
            ]);

            $updated = $this->fetchPostRow($pdo, $id);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Post updated.',
            'data'    => $updated,
        ]);
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $role    = (string) ($payload['role'] ?? '');

        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid post id.',
            ]);
        }

        $isAdmin = in_array($role, ['admin', 'superadmin'], true);

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare(
                'SELECT user_id, is_deleted FROM posts WHERE post_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Post not found.',
                ]);
            }

            if (!$isAdmin && (int) $row['user_id'] !== $userId) {
                return Json::write($response, 403, [
                    'success' => false,
                    'message' => 'You can only delete your own posts.',
                ]);
            }

            // Count comments to decide soft vs hard delete.
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE post_id = :id');
            $stmt->execute([':id' => $id]);
            $commentCount = (int) $stmt->fetchColumn();

            if ($commentCount > 0) {
                // Soft delete: keep row + comments, blank out content.
                $stmt = $pdo->prepare(
                    "UPDATE posts
                     SET is_deleted = 1,
                         title = '[deleted]',
                         content = '[This post was deleted.]'
                     WHERE post_id = :id"
                );
                $stmt->execute([':id' => $id]);

                return Json::write($response, 200, [
                    'success' => true,
                    'message' => 'Post soft-deleted (comments retained).',
                    'data'    => ['mode' => 'soft', 'post_id' => $id],
                ]);
            }

            // Hard delete: no comments, drop everything.
            $pdo->prepare('DELETE FROM post_likes WHERE post_id = :id')->execute([':id' => $id]);
            $stmt = $pdo->prepare('DELETE FROM posts WHERE post_id = :id');
            $stmt->execute([':id' => $id]);

            return Json::write($response, 200, [
                'success' => true,
                'message' => 'Post deleted.',
                'data'    => ['mode' => 'hard', 'post_id' => $id],
            ]);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }
    }

    // -----------------------------------------------------------------
    // LIKES
    // -----------------------------------------------------------------

    public function toggleLike(Request $request, Response $response, array $args): Response
    {
        $id      = (int) ($args['id'] ?? 0);
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);

        if ($id <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid post id.',
            ]);
        }

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare(
                'SELECT post_id, is_deleted FROM posts WHERE post_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Post not found.',
                ]);
            }
            if ((int) $row['is_deleted'] === 1) {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Cannot like a deleted post.',
                ]);
            }

            $stmt = $pdo->prepare(
                'SELECT 1 FROM post_likes WHERE post_id = :pid AND user_id = :uid LIMIT 1'
            );
            $stmt->execute([':pid' => $id, ':uid' => $userId]);
            $already = (bool) $stmt->fetchColumn();

            if ($already) {
                $pdo->prepare(
                    'DELETE FROM post_likes WHERE post_id = :pid AND user_id = :uid'
                )->execute([':pid' => $id, ':uid' => $userId]);
                $pdo->prepare(
                    'UPDATE posts SET likes = GREATEST(likes - 1, 0) WHERE post_id = :id'
                )->execute([':id' => $id]);
                $liked = false;
            } else {
                $pdo->prepare(
                    'INSERT INTO post_likes (post_id, user_id) VALUES (:pid, :uid)'
                )->execute([':pid' => $id, ':uid' => $userId]);
                $pdo->prepare(
                    'UPDATE posts SET likes = likes + 1 WHERE post_id = :id'
                )->execute([':id' => $id]);
                $liked = true;
            }

            $stmt = $pdo->prepare('SELECT likes FROM posts WHERE post_id = :id');
            $stmt->execute([':id' => $id]);
            $count = (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => $liked ? 'Liked.' : 'Like removed.',
            'data'    => [
                'post_id'     => $id,
                'liked_by_me' => $liked,
                'likes'       => $count,
            ],
        ]);
    }

    // -----------------------------------------------------------------
    // COMMENTS
    // -----------------------------------------------------------------

    public function createComment(Request $request, Response $response, array $args): Response
    {
        $postId  = (int) ($args['id'] ?? 0);
        $payload = (array) $request->getAttribute('jwt_payload');
        $userId  = (int) ($payload['user_id'] ?? 0);
        $body    = $this->readJson($request);

        if ($postId <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid post id.',
            ]);
        }

        $content = trim((string) ($body['content'] ?? ''));
        if ($content === '') {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['content' => 'Comment is required.'],
            ]);
        }
        if (mb_strlen($content) > 2000) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['content' => 'Comment must be 2000 characters or fewer.'],
            ]);
        }

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare(
                'SELECT is_deleted FROM posts WHERE post_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $postId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$post) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Post not found.',
                ]);
            }
            if ((int) $post['is_deleted'] === 1) {
                return Json::write($response, 400, [
                    'success' => false,
                    'message' => 'Cannot comment on a deleted post.',
                ]);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO comments (post_id, user_id, content) VALUES (:p, :u, :c)'
            );
            $stmt->execute([':p' => $postId, ':u' => $userId, ':c' => $content]);
            $id = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'SELECT c.comment_id, c.post_id, c.user_id, c.content, c.created_at,
                        u.full_name AS author_name
                 FROM comments c JOIN users u ON u.user_id = c.user_id
                 WHERE c.comment_id = :id'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 201, [
            'success' => true,
            'message' => 'Comment added.',
            'data'    => $row,
        ]);
    }

    public function deleteComment(Request $request, Response $response, array $args): Response
    {
        $postId    = (int) ($args['id'] ?? 0);
        $commentId = (int) ($args['comment_id'] ?? 0);
        $payload   = (array) $request->getAttribute('jwt_payload');
        $userId    = (int) ($payload['user_id'] ?? 0);
        $role      = (string) ($payload['role'] ?? '');
        $isAdmin   = in_array($role, ['admin', 'superadmin'], true);

        if ($postId <= 0 || $commentId <= 0) {
            return Json::write($response, 400, [
                'success' => false,
                'message' => 'Invalid post or comment id.',
            ]);
        }

        try {
            $pdo = Database::getConnection();

            $stmt = $pdo->prepare(
                'SELECT user_id, post_id FROM comments WHERE comment_id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $commentId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || (int) $row['post_id'] !== $postId) {
                return Json::write($response, 404, [
                    'success' => false,
                    'message' => 'Comment not found.',
                ]);
            }

            if (!$isAdmin && (int) $row['user_id'] !== $userId) {
                return Json::write($response, 403, [
                    'success' => false,
                    'message' => 'You can only delete your own comments.',
                ]);
            }

            $pdo->prepare('DELETE FROM comments WHERE comment_id = :id')
                ->execute([':id' => $commentId]);
        } catch (PDOException $e) {
            return Json::write($response, 500, [
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ]);
        }

        return Json::write($response, 200, [
            'success' => true,
            'message' => 'Comment deleted.',
        ]);
    }

    // -----------------------------------------------------------------
    // helpers
    // -----------------------------------------------------------------

    /**
     * Return [post_id => true] for posts already liked by $userId.
     * If $onlyIds is provided we constrain the lookup.
     */
    private function fetchLikedSet(int $userId, array $onlyIds = []): array
    {
        if ($userId <= 0) {
            return [];
        }
        try {
            $pdo = Database::getConnection();
            if (!empty($onlyIds)) {
                $placeholders = implode(',', array_fill(0, count($onlyIds), '?'));
                $stmt = $pdo->prepare(
                    "SELECT post_id FROM post_likes
                     WHERE user_id = ? AND post_id IN ($placeholders)"
                );
                $stmt->execute(array_merge([$userId], $onlyIds));
            } else {
                $stmt = $pdo->prepare(
                    'SELECT post_id FROM post_likes WHERE user_id = :uid'
                );
                $stmt->execute([':uid' => $userId]);
            }
            $set = [];
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
                $set[(int) $pid] = true;
            }
            return $set;
        } catch (PDOException $_e) {
            return [];
        }
    }

    private function fetchPostRow(PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT p.post_id, p.user_id, p.label_id, p.title, p.content,
                    p.likes, p.is_deleted, p.created_at,
                    u.full_name AS author_name,
                    l.name AS label_name,
                    (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.post_id) AS comment_count
             FROM posts p
             JOIN users u ON u.user_id = p.user_id
             LEFT JOIN labels l ON l.label_id = p.label_id
             WHERE p.post_id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function readJson(Request $request): array
    {
        $raw = (string) $request->getBody();
        if ($raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
