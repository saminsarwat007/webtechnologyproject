"""
Generate all proposal diagrams and UI mockups as PNGs.

Run:  docs/.venv/bin/python docs/build_diagrams.py
Output: docs/diagrams/*.png  and  docs/mockups/*.png

Every image is rendered at 200 DPI so the team can drop it straight into the
DOCX or screenshot it without pixelation.
"""
from __future__ import annotations

import os
from pathlib import Path

import matplotlib.patches as mpatches
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, Ellipse, FancyArrowPatch, Rectangle

ROOT = Path(__file__).resolve().parent
DIAGRAMS = ROOT / "diagrams"
MOCKUPS = ROOT / "mockups"
DIAGRAMS.mkdir(parents=True, exist_ok=True)
MOCKUPS.mkdir(parents=True, exist_ok=True)

# A consistent palette across all diagrams
NAVY = "#1e3a5f"
BLUE = "#2e6fb8"
SKY = "#cfe3f5"
AMBER = "#f0a030"
GREEN = "#3aa676"
RED = "#c0392b"
GREY = "#6c757d"
LIGHT = "#f4f6f9"
WHITE = "#ffffff"
TEXT = "#1b1b1b"

DPI = 200


# ---------------------------------------------------------------------------
# 1. USE CASE DIAGRAM
# ---------------------------------------------------------------------------
def use_case_diagram() -> None:
    fig, ax = plt.subplots(figsize=(11, 8), dpi=DPI)
    ax.set_xlim(0, 11)
    ax.set_ylim(0, 8)
    ax.axis("off")

    ax.text(5.5, 7.5, "CareerBridge — Use Case Diagram",
            ha="center", fontsize=15, fontweight="bold", color=NAVY)

    # System boundary
    boundary = FancyBboxPatch(
        (2.6, 0.8), 5.8, 6.0,
        boxstyle="round,pad=0.05,rounding_size=0.2",
        linewidth=2, edgecolor=NAVY, facecolor=LIGHT,
    )
    ax.add_patch(boundary)
    ax.text(5.5, 6.55, "CareerBridge System", ha="center",
            fontsize=11, fontweight="bold", color=NAVY)

    # Actor stick figure helper
    def actor(x: float, y: float, label: str) -> None:
        head = plt.Circle((x, y + 0.25), 0.13, fill=False,
                          linewidth=1.8, edgecolor=TEXT)
        ax.add_patch(head)
        ax.plot([x, x], [y + 0.12, y - 0.25], color=TEXT, linewidth=1.8)
        ax.plot([x - 0.2, x + 0.2], [y - 0.05, y - 0.05], color=TEXT, linewidth=1.8)
        ax.plot([x, x - 0.17], [y - 0.25, y - 0.55], color=TEXT, linewidth=1.8)
        ax.plot([x, x + 0.17], [y - 0.25, y - 0.55], color=TEXT, linewidth=1.8)
        ax.text(x, y - 0.85, label, ha="center", fontsize=10,
                fontweight="bold", color=TEXT)

    actor(1.1, 5.5, "Student")
    actor(1.1, 2.5, "Admin")
    actor(9.9, 4.0, "Super Admin")

    # Use case bubble helper
    def use_case(x: float, y: float, text: str, color: str = SKY) -> tuple:
        w, h = 1.8, 0.55
        e = Ellipse((x, y), w, h, facecolor=color,
                    edgecolor=NAVY, linewidth=1.4)
        ax.add_patch(e)
        ax.text(x, y, text, ha="center", va="center",
                fontsize=8.5, color=TEXT)
        return (x, y, w, h)

    # Column 1 (student)
    uc_register = use_case(3.8, 6.1, "Register / Login")
    uc_browse = use_case(3.8, 5.3, "Browse Jobs")
    uc_apply = use_case(3.8, 4.5, "Apply to Job")
    uc_track = use_case(3.8, 3.7, "Track Applications")
    uc_profile = use_case(3.8, 2.9, "Manage Profile")

    # Column 2 (admin)
    uc_postjob = use_case(7.0, 6.1, "Post / Edit Jobs")
    uc_review = use_case(7.0, 5.3, "Review Applications")
    uc_company = use_case(7.0, 4.5, "Manage Companies")
    uc_analytics = use_case(7.0, 3.7, "View Analytics")
    uc_users = use_case(7.0, 2.9, "Manage Users", color="#ffe3b3")

    # Connector helper
    def connect(ax_pt: tuple, uc_pt: tuple) -> None:
        x1, y1 = ax_pt
        x2, y2, w, h = uc_pt
        # Connect to nearest edge of the ellipse
        edge_x = x2 - (w / 2) if x1 < x2 else x2 + (w / 2)
        ax.plot([x1, edge_x], [y1, y2], color=GREY, linewidth=1.0)

    s = (1.1, 5.25)
    for uc in (uc_register, uc_browse, uc_apply, uc_track, uc_profile):
        connect(s, uc)

    a = (1.1, 2.25)
    for uc in (uc_postjob, uc_review, uc_company, uc_analytics):
        connect(a, uc)
    # Admin also logs in
    connect(a, uc_register)

    sa = (9.9, 3.75)
    for uc in (uc_users, uc_analytics, uc_postjob, uc_review,
               uc_company, uc_register):
        connect(sa, uc)

    # Legend
    ax.text(0.2, 0.4,
            "«include»  Apply requires prior Register / Login\n"
            "«extend»   Manage Users extends Admin privileges (Super Admin only)",
            fontsize=8, color=GREY)

    plt.tight_layout()
    plt.savefig(DIAGRAMS / "01_use_case_diagram.png",
                bbox_inches="tight", facecolor=WHITE)
    plt.close(fig)


# ---------------------------------------------------------------------------
# 2. SYSTEM ARCHITECTURE DIAGRAM (3-tier)
# ---------------------------------------------------------------------------
def architecture_diagram() -> None:
    fig, ax = plt.subplots(figsize=(11, 7), dpi=DPI)
    ax.set_xlim(0, 11)
    ax.set_ylim(0, 7)
    ax.axis("off")
    ax.text(5.5, 6.65, "CareerBridge — System Architecture (3-Tier)",
            ha="center", fontsize=15, fontweight="bold", color=NAVY)

    def tier(x: float, y: float, w: float, h: float, title: str,
             body: list[str], color: str) -> None:
        p = FancyBboxPatch((x, y), w, h,
                           boxstyle="round,pad=0.05,rounding_size=0.15",
                           linewidth=1.6, edgecolor=NAVY, facecolor=color)
        ax.add_patch(p)
        ax.text(x + w / 2, y + h - 0.35, title, ha="center",
                fontsize=11, fontweight="bold", color=NAVY)
        for i, line in enumerate(body):
            ax.text(x + w / 2, y + h - 0.85 - i * 0.42, line,
                    ha="center", fontsize=9, color=TEXT)

    # Tier 1: Presentation (Vue)
    tier(0.6, 1.2, 3.0, 4.6, "Presentation Tier\n(Browser, SPA)",
         ["Vue 3 + Vite", "Pinia store (auth)",
          "Vue Router (role guards)", "Tailwind CSS",
          "Chart.js dashboards", "Axios HTTP client"], "#dce9f7")

    # Tier 2: Application (Slim API)
    tier(4.0, 1.2, 3.0, 4.6, "Application Tier\n(REST API)",
         ["PHP 7.4+ with Slim 4", "JWT middleware (HS256)",
          "CORS middleware", "6 module controllers",
          "Global JSON envelope", "Error handler"], "#d9f0e1")

    # Tier 3: Data (MySQL)
    tier(7.4, 1.2, 3.0, 4.6, "Data Tier\n(RDBMS)",
         ["MySQL 5.7+ / MariaDB", "5 tables (InnoDB)",
          "PDO prepared stmts", "FK referential integrity",
          "UNIQUE (job, user)", "ENUM types"], "#fde7c7")

    # Arrows between tiers
    def arrow(x1, x2, y, label_top, label_bot):
        a1 = FancyArrowPatch((x1, y + 0.12), (x2, y + 0.12),
                             arrowstyle="->,head_length=10,head_width=6",
                             color=NAVY, linewidth=1.5)
        a2 = FancyArrowPatch((x2, y - 0.12), (x1, y - 0.12),
                             arrowstyle="->,head_length=10,head_width=6",
                             color=NAVY, linewidth=1.5)
        ax.add_patch(a1)
        ax.add_patch(a2)
        mx = (x1 + x2) / 2
        ax.text(mx, y + 0.32, label_top, ha="center", fontsize=8, color=NAVY)
        ax.text(mx, y - 0.42, label_bot, ha="center", fontsize=8, color=NAVY)

    arrow(3.6, 4.0, 3.5, "JSON + Bearer JWT", "HTTP responses")
    arrow(7.0, 7.4, 3.5, "PDO SQL (prepared)", "Result sets")

    # Footer note
    ax.text(5.5, 0.55,
            "All API calls use Bearer JWT; passwords stored with bcrypt; "
            "every query uses PDO prepared statements.",
            ha="center", fontsize=8.5, color=GREY, style="italic")

    plt.tight_layout()
    plt.savefig(DIAGRAMS / "02_architecture_diagram.png",
                bbox_inches="tight", facecolor=WHITE)
    plt.close(fig)


# ---------------------------------------------------------------------------
# 3. ER DIAGRAM
# ---------------------------------------------------------------------------
def er_diagram() -> None:
    """Entity-Relationship diagram with orthogonal line routing so
    relationship lines never pass through entity boxes."""
    fig, ax = plt.subplots(figsize=(14, 9), dpi=DPI)
    ax.set_xlim(0, 14)
    ax.set_ylim(0, 9)
    ax.axis("off")
    ax.text(7, 8.65, "CareerBridge — Entity-Relationship Diagram",
            ha="center", fontsize=15, fontweight="bold", color=NAVY)

    def entity(x: float, y: float, name: str, attrs: list[str],
               w: float = 3.0) -> dict:
        """Draw an entity box anchored at top-left (x, y).
        Returns dict with geometry helpers."""
        header_h = 0.45
        row_h = 0.32
        h = header_h + row_h * len(attrs) + 0.15
        # Header
        ax.add_patch(Rectangle((x, y - header_h), w, header_h,
                               facecolor=NAVY, edgecolor=NAVY))
        ax.text(x + w / 2, y - header_h / 2, name,
                ha="center", va="center", color=WHITE,
                fontsize=10.5, fontweight="bold")
        # Body
        ax.add_patch(Rectangle((x, y - h), w, h - header_h,
                               facecolor=WHITE, edgecolor=NAVY, linewidth=1.2))
        for i, attr in enumerate(attrs):
            ax.text(x + 0.1, y - header_h - 0.22 - i * row_h, attr,
                    fontsize=8.4, color=TEXT, va="center")
        return {
            "left":   (x,           y - h / 2),
            "right":  (x + w,       y - h / 2),
            "top":    (x + w / 2,   y),
            "bottom": (x + w / 2,   y - h),
            "x0": x, "x1": x + w, "y0": y - h, "y1": y,
        }

    # Layout: 2 rows x 3 cols with applications shifted right
    users = entity(0.3, 8.1, "users", [
        "PK user_id INT",
        "   full_name VARCHAR(100)",
        "   email VARCHAR(150) UNIQUE",
        "   password_hash VARCHAR(255)",
        "   role ENUM(student/admin/sa)",
        "   created_at TIMESTAMP",
    ])

    companies = entity(5.3, 8.1, "companies", [
        "PK company_id INT",
        "   name VARCHAR(150)",
        "   industry VARCHAR(100)",
        "   location VARCHAR(150)",
        "   description TEXT",
        "FK created_by → users",
        "   created_at TIMESTAMP",
    ])

    applications = entity(10.3, 8.1, "applications", [
        "PK application_id INT",
        "FK job_id → jobs",
        "FK user_id → users",
        "   cover_letter TEXT",
        "   status ENUM(pend/rev/acc/rej)",
        "   applied_at TIMESTAMP",
        "   updated_at TIMESTAMP",
        "UQ (job_id, user_id)",
    ])

    profiles = entity(0.3, 3.6, "student_profiles", [
        "PK profile_id INT",
        "FK user_id → users (UNIQUE)",
        "   matric_no VARCHAR(20) UNIQUE",
        "   programme VARCHAR(100)",
        "   cgpa DECIMAL(3,2)",
        "   skills TEXT",
        "   resume_text TEXT",
    ])

    jobs = entity(5.3, 3.6, "jobs", [
        "PK job_id INT",
        "FK company_id → companies",
        "FK posted_by → users",
        "   title VARCHAR(150)",
        "   type ENUM(intern/full/part)",
        "   description TEXT",
        "   requirements TEXT",
        "   deadline DATE",
        "   is_active BOOLEAN",
    ])

    def ortho(points: list[tuple], label: str, c1: str, c2: str,
              label_xy: tuple) -> None:
        """Draw orthogonal (right-angle) polyline through `points`."""
        xs = [p[0] for p in points]
        ys = [p[1] for p in points]
        ax.plot(xs, ys, color=GREY, linewidth=1.4,
                solid_joinstyle="miter", solid_capstyle="butt")
        # Endpoints small dots
        ax.plot(xs[0], ys[0], "o", color=NAVY, markersize=4)
        ax.plot(xs[-1], ys[-1], "o", color=NAVY, markersize=4)
        # Cardinality labels near endpoints
        ax.text(xs[0] + 0.08, ys[0] + 0.15, c1, fontsize=9,
                color=NAVY, fontweight="bold")
        ax.text(xs[-1] - 0.18, ys[-1] + 0.15, c2, fontsize=9,
                color=NAVY, fontweight="bold")
        # Relationship label
        ax.text(label_xy[0], label_xy[1], label, ha="center",
                fontsize=9, color=NAVY, fontweight="bold",
                bbox=dict(facecolor=WHITE, edgecolor=NAVY,
                          linewidth=0.8, pad=2.5,
                          boxstyle="round,pad=0.2"))

    # users.bottom → profiles.top  (1 : 1, vertical straight line)
    ortho([users["bottom"], profiles["top"]],
          "has", "1", "1",
          label_xy=((users["bottom"][0] + profiles["top"][0]) / 2,
                    (users["bottom"][1] + profiles["top"][1]) / 2))

    # companies.bottom → jobs.top  (1 : N, vertical straight line)
    ortho([companies["bottom"], jobs["top"]],
          "offers", "1", "N",
          label_xy=((companies["bottom"][0] + jobs["top"][0]) / 2,
                    (companies["bottom"][1] + jobs["top"][1]) / 2))

    # users.right → companies.left (1 : N, horizontal straight line)
    y_mid = (users["right"][1] + companies["left"][1]) / 2
    ortho([users["right"], (users["right"][0] + 0.3, users["right"][1]),
           (companies["left"][0] - 0.3, companies["left"][1]),
           companies["left"]],
          "creates", "1", "N",
          label_xy=((users["right"][0] + companies["left"][0]) / 2, y_mid + 0.25))

    # users.right → jobs (posts)  — route down then right then into jobs.left
    ortho([(users["x1"], users["y0"] + 0.35),
           (users["x1"] + 0.6, users["y0"] + 0.35),
           (users["x1"] + 0.6, jobs["left"][1]),
           jobs["left"]],
          "posts", "1", "N",
          label_xy=(users["x1"] + 1.2, users["y0"] + 0.2))

    # jobs.right → applications.bottom — route up-right
    ortho([jobs["right"],
           (jobs["right"][0] + 0.6, jobs["right"][1]),
           (jobs["right"][0] + 0.6, applications["bottom"][1] - 0.6),
           (applications["bottom"][0], applications["bottom"][1] - 0.6),
           applications["bottom"]],
          "receives", "1", "N",
          label_xy=(applications["bottom"][0] - 0.3, applications["bottom"][1] - 0.9))

    # users.top — right past applications — then into applications.top (submits)
    ortho([(users["x1"] - 0.4, users["y1"]),
           (users["x1"] - 0.4, users["y1"] + 0.35),
           (applications["top"][0], users["y1"] + 0.35),
           applications["top"]],
          "submits", "1", "N",
          label_xy=((users["x1"] + applications["top"][0]) / 2,
                    users["y1"] + 0.55))

    # Legend
    ax.text(0.3, 0.35,
            "Notation:  PK = Primary Key   FK = Foreign Key   "
            "UQ = Unique Constraint   Cardinality shown as 1 : N / 1 : 1",
            fontsize=8, color=GREY, style="italic")

    plt.tight_layout()
    plt.savefig(DIAGRAMS / "03_er_diagram.png",
                bbox_inches="tight", facecolor=WHITE)
    plt.close(fig)


# ---------------------------------------------------------------------------
# 4. GANTT CHART — weekly ownership
# ---------------------------------------------------------------------------
def gantt_chart() -> None:
    fig, ax = plt.subplots(figsize=(12, 6.5), dpi=DPI)
    ax.text(4.5, 15.3, "CareerBridge — 9-Week Project Schedule",
            ha="center", fontsize=14, fontweight="bold", color=NAVY)

    members = ["Areeb (M1+M6)", "Samin (M2+M3)", "Mariam (M4+M5)"]
    member_color = {
        "Areeb (M1+M6)": BLUE,
        "Samin (M2+M3)": GREEN,
        "Mariam (M4+M5)": AMBER,
    }

    tasks = [
        ("Areeb (M1+M6)", "Project setup (composer, schema, PDO)", 6, 1),
        ("Areeb (M1+M6)", "Auth module (AuthController, JWT mw)", 7, 1),
        ("Areeb (M1+M6)", "Auth frontend (login, register, guards)", 8, 1),
        ("Areeb (M1+M6)", "Admin users + seed + error handler", 9, 1),
        ("Areeb (M1+M6)", "Notifications (toast, confirm, AdminUsers)", 10, 1),
        ("Areeb (M1+M6)", "Security hardening", 11, 1),
        ("Areeb (M1+M6)", "Deployment configs + release", 12, 3),
        ("Samin (M2+M3)", "Vue setup (Vite, Pinia, Tailwind)", 6, 1),
        ("Samin (M2+M3)", "Jobs API (full CRUD)", 7, 1),
        ("Samin (M2+M3)", "Jobs frontend (browse, detail, manage)", 8, 1),
        ("Samin (M2+M3)", "Applications API (full CRUD)", 9, 1),
        ("Samin (M2+M3)", "Applications frontend", 10, 1),
        ("Samin (M2+M3)", "Jobs/Apps integration polish", 11, 1),
        ("Samin (M2+M3)", "UX polish + responsive", 12, 3),
        ("Mariam (M4+M5)", "Base components (NavBar, badges)", 6, 1),
        ("Mariam (M4+M5)", "Companies API (full CRUD)", 7, 1),
        ("Mariam (M4+M5)", "Companies frontend", 8, 1),
        ("Mariam (M4+M5)", "Analytics API + Profile upsert", 9, 1),
        ("Mariam (M4+M5)", "Analytics frontend (dashboards)", 10, 1),
        ("Mariam (M4+M5)", "Reporting polish + a11y", 11, 1),
        ("Mariam (M4+M5)", "Final docs + demo script", 12, 3),
    ]

    # Lay them out: one row per task
    y = len(tasks)
    for i, (member, label, start, span) in enumerate(tasks):
        row = y - i
        ax.barh(row, span, left=start, height=0.65,
                color=member_color[member], edgecolor=NAVY, linewidth=0.6)
        ax.text(start + span / 2, row, label, va="center", ha="center",
                fontsize=7.6, color=WHITE, fontweight="bold")

    ax.set_xlim(5.5, 15.5)
    ax.set_ylim(0.3, len(tasks) + 1.2)
    ax.set_xticks([6, 7, 8, 9, 10, 11, 12, 13, 14])
    ax.set_xticklabels(["Wk6", "Wk7", "Wk8", "Wk9", "Wk10",
                        "Wk11", "Wk12", "Wk13", "Wk14"])
    ax.set_yticks([])
    ax.grid(axis="x", linestyle=":", alpha=0.5)
    ax.set_axisbelow(True)

    patches = [mpatches.Patch(color=c, label=m)
               for m, c in member_color.items()]
    ax.legend(handles=patches, loc="upper right",
              fontsize=9, frameon=False)

    for s in ax.spines.values():
        s.set_visible(False)

    plt.tight_layout()
    plt.savefig(DIAGRAMS / "04_gantt_chart.png",
                bbox_inches="tight", facecolor=WHITE)
    plt.close(fig)


# ---------------------------------------------------------------------------
# 5. UI MOCKUPS (simple wireframe style)
# ---------------------------------------------------------------------------
def _mockup_frame(ax, title: str) -> None:
    ax.set_xlim(0, 10)
    ax.set_ylim(0, 7)
    ax.axis("off")
    # Browser chrome
    ax.add_patch(Rectangle((0, 6.5), 10, 0.5, facecolor="#e6e6e6",
                           edgecolor=GREY))
    for i, c in enumerate(["#e74c3c", "#f1c40f", "#2ecc71"]):
        ax.add_patch(plt.Circle((0.25 + i * 0.3, 6.75), 0.08,
                                color=c))
    ax.add_patch(FancyBboxPatch((1.5, 6.6), 7.5, 0.32,
                                boxstyle="round,pad=0.02,rounding_size=0.1",
                                facecolor=WHITE, edgecolor=GREY, linewidth=0.8))
    ax.text(1.7, 6.76, "careerbridge.my" + " " * 2 + f" — {title}",
            fontsize=8, color=GREY)


def _navbar(ax, active: str, role: str = "student") -> None:
    ax.add_patch(Rectangle((0, 6.0), 10, 0.5, facecolor=NAVY,
                           edgecolor=NAVY))
    ax.text(0.3, 6.25, "CareerBridge", fontsize=10,
            fontweight="bold", color=WHITE)
    links = (["Browse Jobs", "My Applications", "Profile"] if role == "student"
             else ["Dashboard", "Jobs", "Applications", "Companies"])
    for i, link in enumerate(links):
        x = 3.2 + i * 1.5
        color = AMBER if link == active else WHITE
        weight = "bold" if link == active else "normal"
        ax.text(x, 6.25, link, fontsize=8.5, color=color, fontweight=weight)
    ax.text(9.7, 6.25, "Log out", fontsize=8.5, color=WHITE, ha="right")


def _input(ax, x, y, w, h, placeholder: str) -> None:
    ax.add_patch(FancyBboxPatch((x, y), w, h,
                                boxstyle="round,pad=0.02,rounding_size=0.1",
                                facecolor=WHITE, edgecolor=GREY, linewidth=1))
    ax.text(x + 0.15, y + h / 2, placeholder, fontsize=8,
            color=GREY, va="center")


def _button(ax, x, y, w, h, label: str, color: str = NAVY) -> None:
    ax.add_patch(FancyBboxPatch((x, y), w, h,
                                boxstyle="round,pad=0.02,rounding_size=0.1",
                                facecolor=color, edgecolor=color))
    ax.text(x + w / 2, y + h / 2, label, fontsize=8.5,
            color=WHITE, ha="center", va="center", fontweight="bold")


def mockup_login() -> None:
    fig, ax = plt.subplots(figsize=(10, 7), dpi=DPI)
    _mockup_frame(ax, "Login")
    ax.add_patch(Rectangle((0, 0), 10, 6.5, facecolor=LIGHT))
    # Card
    ax.add_patch(FancyBboxPatch((3, 1.5), 4, 4.5,
                                boxstyle="round,pad=0.1,rounding_size=0.2",
                                facecolor=WHITE, edgecolor=GREY, linewidth=1.2))
    ax.text(5, 5.5, "Sign in to CareerBridge", ha="center",
            fontsize=13, fontweight="bold", color=NAVY)
    ax.text(5, 5.1, "Enter your UTM credentials", ha="center",
            fontsize=8, color=GREY)
    _input(ax, 3.3, 4.1, 3.4, 0.45, "Email")
    _input(ax, 3.3, 3.4, 3.4, 0.45, "Password")
    _button(ax, 3.3, 2.5, 3.4, 0.55, "Log in")
    ax.text(5, 2.0, "Don't have an account?  Register",
            ha="center", fontsize=8.5, color=BLUE)
    plt.tight_layout()
    plt.savefig(MOCKUPS / "01_login.png",
                bbox_inches="tight", facecolor=WHITE)
    plt.close(fig)


def mockup_browse_jobs() -> None:
    fig, ax = plt.subplots(figsize=(10, 7), dpi=DPI)
    _mockup_frame(ax, "Browse Jobs")
    _navbar(ax, "Browse Jobs", "student")
    ax.add_patch(Rectangle((0, 0), 10, 6.0, facecolor=LIGHT))
    ax.text(0.3, 5.6, "Browse Internships & Jobs",
            fontsize=13, fontweight="bold", color=NAVY)
    # Filter row
    _input(ax, 0.3, 4.9, 4.0, 0.45, "Search title or company...")
    _input(ax, 4.4, 4.9, 2.3, 0.45, "Type: All")
    _input(ax, 6.8, 4.9, 2.9, 0.45, "Company: All")
    # Job cards (2x3 grid)
    titles = [
        ("Software Engineering Intern", "Axiata Digital", "Kuala Lumpur"),
        ("Data Analyst Intern", "CIMB Bank", "Kuala Lumpur"),
        ("Full-Stack Developer", "Grab Malaysia", "Cyberjaya"),
        ("UX Designer Intern", "Maxis Berhad", "Petaling Jaya"),
        ("DevOps Intern", "Axiata Digital", "Kuala Lumpur"),
        ("Backend Engineer", "Grab Malaysia", "Cyberjaya"),
    ]
    for i, (title, co, loc) in enumerate(titles):
        col = i % 3
        row = i // 3
        x = 0.3 + col * 3.2
        y = 3.2 - row * 1.6
        ax.add_patch(FancyBboxPatch((x, y), 3.0, 1.4,
                                    boxstyle="round,pad=0.05,rounding_size=0.1",
                                    facecolor=WHITE, edgecolor=GREY,
                                    linewidth=0.9))
        ax.text(x + 0.15, y + 1.1, title, fontsize=8.5,
                fontweight="bold", color=TEXT)
        ax.text(x + 0.15, y + 0.85, co, fontsize=7.5, color=BLUE)
        ax.text(x + 0.15, y + 0.65, loc, fontsize=7.5, color=GREY)
        ax.add_patch(FancyBboxPatch((x + 0.15, y + 0.3), 1.0, 0.25,
                                    boxstyle="round,pad=0.02,rounding_size=0.1",
                                    facecolor=SKY, edgecolor=BLUE))
        ax.text(x + 0.65, y + 0.42, "internship",
                fontsize=7, color=NAVY, ha="center", va="center")
        _button(ax, x + 2.1, y + 0.15, 0.75, 0.32, "View", NAVY)
    plt.tight_layout()
    plt.savefig(MOCKUPS / "02_browse_jobs.png",
                bbox_inches="tight", facecolor=WHITE)
    plt.close(fig)


def mockup_student_dashboard() -> None:
    fig, ax = plt.subplots(figsize=(10, 7), dpi=DPI)
    _mockup_frame(ax, "Student Dashboard")
    _navbar(ax, "Profile", "student")
    ax.add_patch(Rectangle((0, 0), 10, 6.0, facecolor=LIGHT))
    ax.text(0.3, 5.6, "Welcome back, Samin", fontsize=13,
            fontweight="bold", color=NAVY)
    ax.text(0.3, 5.25, "Here's a summary of your applications.",
            fontsize=9, color=GREY)

    # Stat cards
    stats = [("Total", "8", NAVY),
             ("Pending", "3", AMBER),
             ("Accepted", "2", GREEN),
             ("Rejected", "1", RED)]
    for i, (label, value, color) in enumerate(stats):
        x = 0.3 + i * 2.45
        ax.add_patch(FancyBboxPatch((x, 3.7), 2.25, 1.2,
                                    boxstyle="round,pad=0.05,rounding_size=0.1",
                                    facecolor=WHITE, edgecolor=GREY))
        ax.text(x + 0.15, 4.65, label, fontsize=9, color=GREY)
        ax.text(x + 0.15, 4.05, value, fontsize=22,
                fontweight="bold", color=color)

    # Recent applications table
    ax.add_patch(FancyBboxPatch((0.3, 0.4), 9.4, 3.0,
                                boxstyle="round,pad=0.05,rounding_size=0.1",
                                facecolor=WHITE, edgecolor=GREY))
    ax.text(0.5, 3.15, "Recent applications", fontsize=10,
            fontweight="bold", color=NAVY)
    headers = ["Job", "Company", "Applied", "Status"]
    xs = [0.5, 4.0, 6.0, 8.0]
    for x, h in zip(xs, headers):
        ax.text(x, 2.75, h, fontsize=8.5, fontweight="bold", color=GREY)
    rows = [
        ("Software Engineering Intern", "Axiata Digital", "2 May", "pending", AMBER),
        ("Data Analyst Intern", "CIMB Bank", "30 Apr", "accepted", GREEN),
        ("UX Designer Intern", "Maxis", "28 Apr", "reviewed", BLUE),
        ("DevOps Intern", "Grab Malaysia", "25 Apr", "rejected", RED),
    ]
    for i, (job, co, date, status, color) in enumerate(rows):
        y = 2.35 - i * 0.45
        ax.text(0.5, y, job, fontsize=8, color=TEXT)
        ax.text(4.0, y, co, fontsize=8, color=TEXT)
        ax.text(6.0, y, date, fontsize=8, color=TEXT)
        ax.add_patch(FancyBboxPatch((8.0, y - 0.1), 1.2, 0.28,
                                    boxstyle="round,pad=0.02,rounding_size=0.1",
                                    facecolor=color, edgecolor=color))
        ax.text(8.6, y + 0.04, status, fontsize=7.5,
                color=WHITE, ha="center", va="center", fontweight="bold")

    plt.tight_layout()
    plt.savefig(MOCKUPS / "03_student_dashboard.png",
                bbox_inches="tight", facecolor=WHITE)
    plt.close(fig)


def mockup_admin_dashboard() -> None:
    fig, ax = plt.subplots(figsize=(10, 7), dpi=DPI)
    _mockup_frame(ax, "Admin Dashboard")
    _navbar(ax, "Dashboard", "admin")
    ax.add_patch(Rectangle((0, 0), 10, 6.0, facecolor=LIGHT))
    ax.text(0.3, 5.6, "Admin Analytics", fontsize=13,
            fontweight="bold", color=NAVY)
    ax.text(0.3, 5.25, "Live platform metrics",
            fontsize=9, color=GREY)

    # Stat cards
    stats = [("Total Jobs", "10", NAVY),
             ("Active Jobs", "10", BLUE),
             ("Applications", "47", AMBER),
             ("This Week", "12", GREEN)]
    for i, (label, value, color) in enumerate(stats):
        x = 0.3 + i * 2.45
        ax.add_patch(FancyBboxPatch((x, 3.7), 2.25, 1.2,
                                    boxstyle="round,pad=0.05,rounding_size=0.1",
                                    facecolor=WHITE, edgecolor=GREY))
        ax.text(x + 0.15, 4.65, label, fontsize=9, color=GREY)
        ax.text(x + 0.15, 4.05, value, fontsize=22,
                fontweight="bold", color=color)

    # Chart area
    ax.add_patch(FancyBboxPatch((0.3, 0.4), 9.4, 3.0,
                                boxstyle="round,pad=0.05,rounding_size=0.1",
                                facecolor=WHITE, edgecolor=GREY))
    ax.text(0.5, 3.15, "Applications by status",
            fontsize=10, fontweight="bold", color=NAVY)
    # Fake bar chart
    labels = ["pending", "reviewed", "accepted", "rejected"]
    values = [18, 12, 10, 7]
    colors = [AMBER, BLUE, GREEN, RED]
    base_x, base_y, w = 1.0, 0.8, 1.6
    max_v = max(values)
    for i, (label, v, color) in enumerate(zip(labels, values, colors)):
        h = (v / max_v) * 1.9
        ax.add_patch(Rectangle((base_x + i * (w + 0.4), base_y), w, h,
                               facecolor=color, edgecolor=NAVY, linewidth=0.6))
        ax.text(base_x + i * (w + 0.4) + w / 2, base_y + h + 0.08,
                str(v), ha="center", fontsize=9, fontweight="bold", color=TEXT)
        ax.text(base_x + i * (w + 0.4) + w / 2, base_y - 0.2,
                label, ha="center", fontsize=8.5, color=TEXT)

    plt.tight_layout()
    plt.savefig(MOCKUPS / "04_admin_dashboard.png",
                bbox_inches="tight", facecolor=WHITE)
    plt.close(fig)


def main() -> None:
    print("Generating diagrams...")
    use_case_diagram()
    architecture_diagram()
    er_diagram()
    gantt_chart()
    print("Generating mockups...")
    mockup_login()
    mockup_browse_jobs()
    mockup_student_dashboard()
    mockup_admin_dashboard()
    # List outputs
    for p in sorted(DIAGRAMS.glob("*.png")):
        print("  ", p.relative_to(ROOT.parent))
    for p in sorted(MOCKUPS.glob("*.png")):
        print("  ", p.relative_to(ROOT.parent))
    print("Done.")


if __name__ == "__main__":
    main()
