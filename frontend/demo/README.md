# Samin's Progress Demo — Auto-recording

This folder contains everything you need to produce the progress video for Module 2 (Jobs) and Module 3 (Applications).

The plan:
1. **Playwright drives the browser** through the demo flow and records a full HD video.
2. **You add voiceover and face cam** in CapCut / iMovie / your editor of choice.
3. **Upload to YouTube** as Unlisted, paste the link.

---

## Prereqs (one-time)

You need three things running:

1. **MySQL database seeded.** From repo root:
   ```bash
   mysql -u root < database/schema.sql
   mysql -u root < database/seed.sql
   ```

2. **Backend on :8000.** From repo root:
   ```bash
   cd backend
   php -S localhost:8000 -t public
   ```
   Leave this terminal open.

3. **Frontend dev server** — Playwright will auto-start it on :5173, no action needed.

---

## Run the recording

From `frontend/`:
```bash
npx playwright test --config=demo/playwright.demo.config.js
```

You'll see a Chromium window open (because `headless: false`) and walk through the entire demo. Keep your hands off the mouse / keyboard while it runs — about 90 seconds.

When it finishes, the video is saved at:
```
frontend/demo/output/<spec-name>/video.webm
```

---

## Convert to mp4 for editing (optional but recommended)

WebM works in some editors but mp4 is friendlier:
```bash
ffmpeg -i frontend/demo/output/Samin*/video.webm frontend/demo/screen.mp4
```

If you don't have ffmpeg:
```bash
brew install ffmpeg
```

---

## Add voiceover + face cam

Open `screen.mp4` (or the `.webm`) in any editor:

- **iMovie** (Mac built-in, easiest)
- **CapCut Desktop** (free, more features)
- **DaVinci Resolve** (free, professional, overkill for this)

Drop the screen recording on the timeline, then:
- Record your voiceover narrating from `voiceover.md`.
- Record a face cam clip on your laptop webcam, drop it as a small circle/square overlay in a corner.
- Trim, export as 1080p mp4.

---

## Submission

1. Upload the final mp4 to your YouTube as **Unlisted**.
2. Paste the YouTube link into the assignment submission box on the LMS.

---

## Files in this folder

| File | What it is |
|---|---|
| `demo.spec.js` | The Playwright script that drives the demo (10 scenes covering M2 + M3) |
| `playwright.demo.config.js` | Records 1080p video, slows playback so it's narratable |
| `voiceover.md` | Scene-by-scene voiceover script (~2:30) |
| `README.md` | This file |
| `output/` | Generated. Holds the recorded video. |
