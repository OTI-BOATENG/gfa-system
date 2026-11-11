import os
import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import qn, nsdecls

def create_report():
    doc = Document()

    # Page Margins: 1 inch (72pt)
    sections = doc.sections
    for section in sections:
        section.top_margin = Inches(1)
        section.bottom_margin = Inches(1)
        section.left_margin = Inches(1)
        section.right_margin = Inches(1)

    # Color Palette
    COLOR_PRIMARY = RGBColor(15, 76, 129)     # Deep Navy / Classic Blue
    COLOR_TEAL = RGBColor(11, 92, 86)         # Deep Teal
    COLOR_DARK = RGBColor(15, 23, 42)          # Slate 900
    COLOR_MUTED = RGBColor(100, 116, 139)     # Slate 500
    HEX_PRIMARY = "0F4C81"
    HEX_TEAL = "0B5C56"
    HEX_LIGHT_BG = "F8FAFC"
    HEX_BORDER = "CBD5E1"

    # Set Base Style
    normal_style = doc.styles['Normal']
    normal_style.font.name = 'Calibri'
    normal_style.font.size = Pt(11)
    normal_style.font.color.rgb = COLOR_DARK
    normal_style.paragraph_format.line_spacing = 1.18
    normal_style.paragraph_format.space_after = Pt(6)

    def add_title(text):
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_before = Pt(12)
        p.paragraph_format.space_after = Pt(4)
        run = p.add_run(text)
        run.bold = True
        run.font.size = Pt(24)
        run.font.color.rgb = COLOR_PRIMARY
        return p

    def add_subtitle(text):
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(18)
        run = p.add_run(text)
        run.bold = True
        run.font.size = Pt(14)
        run.font.color.rgb = COLOR_TEAL
        return p

    def add_h1(text):
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(18)
        p.paragraph_format.space_after = Pt(6)
        p.paragraph_format.keep_with_next = True
        run = p.add_run(text)
        run.bold = True
        run.font.size = Pt(16)
        run.font.color.rgb = COLOR_PRIMARY
        return p

    def add_h2(text):
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(14)
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.keep_with_next = True
        run = p.add_run(text)
        run.bold = True
        run.font.size = Pt(13)
        run.font.color.rgb = COLOR_TEAL
        return p

    def add_h3(text):
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(10)
        p.paragraph_format.space_after = Pt(3)
        p.paragraph_format.keep_with_next = True
        run = p.add_run(text)
        run.bold = True
        run.font.size = Pt(11.5)
        run.font.color.rgb = COLOR_DARK
        return p

    def add_callout(text, title="KEY SECURITY HIGHLIGHT"):
        tbl = doc.add_table(rows=1, cols=1)
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        cell = tbl.cell(0, 0)
        cell.width = Inches(6.5)
        
        # XML styling for borders and background shading
        tcPr = cell._tc.get_or_add_tcPr()
        shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="F1F5F9"/>')
        borders = parse_xml(
            f'<w:tcBorders {nsdecls("w")}>'
            f'<w:top w:val="none"/>'
            f'<w:left w:val="single" w:sz="36" w:space="0" w:color="{HEX_PRIMARY}"/>'
            f'<w:bottom w:val="none"/>'
            f'<w:right w:val="none"/>'
            f'</w:tcBorders>'
        )
        tcPr.append(shd)
        tcPr.append(borders)

        cp = cell.paragraphs[0]
        cp.paragraph_format.space_before = Pt(6)
        cp.paragraph_format.space_after = Pt(2)
        r_title = cp.add_run(f"★ {title}\n")
        r_title.bold = True
        r_title.font.size = Pt(10)
        r_title.font.color.rgb = COLOR_PRIMARY

        r_text = cp.add_run(text)
        r_text.font.size = Pt(10.5)
        r_text.font.color.rgb = COLOR_DARK
        
        # Spacer
        sp = doc.add_paragraph()
        sp.paragraph_format.space_after = Pt(4)

    def style_table(tbl, col_widths, headers, data):
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        tbl.autofit = False
        
        # Header Row
        hdr_cells = tbl.rows[0].cells
        for i, header_text in enumerate(headers):
            hdr_cells[i].text = header_text
            hdr_cells[i].width = col_widths[i]
            tcPr = hdr_cells[i]._tc.get_or_add_tcPr()
            shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{HEX_PRIMARY}"/>')
            tcPr.append(shd)
            p = hdr_cells[i].paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT
            p.paragraph_format.space_before = Pt(5)
            p.paragraph_format.space_after = Pt(5)
            for r in p.runs:
                r.bold = True
                r.font.size = Pt(10)
                r.font.color.rgb = RGBColor(255, 255, 255)

        # Data Rows
        for r_idx, row_data in enumerate(data):
            row_cells = tbl.add_row().cells
            bg_color = "FFFFFF" if r_idx % 2 == 0 else "F8FAFC"
            for c_idx, cell_value in enumerate(row_data):
                row_cells[c_idx].text = cell_value
                row_cells[c_idx].width = col_widths[c_idx]
                tcPr = row_cells[c_idx]._tc.get_or_add_tcPr()
                shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{bg_color}"/>')
                borders = parse_xml(
                    f'<w:tcBorders {nsdecls("w")}>'
                    f'<w:top w:val="single" w:sz="4" w:space="0" w:color="{HEX_BORDER}"/>'
                    f'<w:bottom w:val="single" w:sz="4" w:space="0" w:color="{HEX_BORDER}"/>'
                    f'<w:left w:val="none"/>'
                    f'<w:right w:val="none"/>'
                    f'</w:tcBorders>'
                )
                tcPr.append(shd)
                tcPr.append(borders)
                p = row_cells[c_idx].paragraphs[0]
                p.paragraph_format.space_before = Pt(4)
                p.paragraph_format.space_after = Pt(4)
                for r in p.runs:
                    r.font.size = Pt(9.5)
                    r.font.color.rgb = COLOR_DARK

        doc.add_paragraph().paragraph_format.space_after = Pt(6)

    def add_image_figure(img_path, caption_title, caption_desc):
        if os.path.exists(img_path):
            p_img = doc.add_paragraph()
            p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
            p_img.paragraph_format.space_before = Pt(10)
            p_img.paragraph_format.space_after = Pt(4)
            p_img.paragraph_format.keep_with_next = True
            run_img = p_img.add_run()
            run_img.add_picture(img_path, width=Inches(5.8))

            p_cap = doc.add_paragraph()
            p_cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
            p_cap.paragraph_format.space_before = Pt(2)
            p_cap.paragraph_format.space_after = Pt(12)
            r_cap_title = p_cap.add_run(caption_title + "\n")
            r_cap_title.bold = True
            r_cap_title.font.size = Pt(9.5)
            r_cap_title.font.color.rgb = COLOR_PRIMARY
            r_cap_desc = p_cap.add_run(caption_desc)
            r_cap_desc.italic = True
            r_cap_desc.font.size = Pt(9)
            r_cap_desc.font.color.rgb = COLOR_MUTED
        else:
            p_err = doc.add_paragraph()
            p_err.add_run(f"[Screenshot unavailable at: {img_path}]").italic = True

    # ==========================
    # DOCUMENT CONTENT
    # ==========================

    # Title & Header
    add_title("Ghana Football Association (GFA) Management Information System")
    add_subtitle("Comprehensive System Security Report: CIA Triad, Two-Factor Authentication, & Salt/Pepper Architecture")

    # Metadata Table
    meta_tbl = doc.add_table(rows=1, cols=2)
    meta_headers = ["Academic & Candidate Identification", "Details"]
    meta_data = [
        ["Candidate / Author Name", "Daniel Boateng (Oti-Boateng)"],
        ["Student Identification (ID)", "26130005"],
        ["Institution & Department", "Pentecost University / Department of Information Technology"],
        ["Course / Module", "Information Security & Secure System Development"],
        ["GitHub Account & Repository", "https://github.com/OTI-BOATENG/gfa-system"],
        ["Cloud Live Deployment URL", "https://gfa-system-seven.vercel.app"],
        ["Core Cryptographic Stack", "Argon2id (m=65536, t=4, p=1), 16-Byte CSPRNG Salt, Server Pepper, Time-based 2FA OTP, RBAC, Automated Disaster Recovery Backups"],
    ]
    style_table(meta_tbl, [Inches(2.5), Inches(4.0)], meta_headers, meta_data)

    # 1. EXECUTIVE SUMMARY
    add_h1("1. Executive Summary & System Overview")
    doc.add_paragraph(
        "The Ghana Football Association (GFA) Management Information System is an enterprise-grade secure web portal "
        "engineered in PHP and MySQL to manage association operations, national team squads, executive committees, and "
        "official membership rosters. Built from the ground up under rigorous defense-in-depth principles, the architecture "
        "addresses modern cyber threat vectors, credential breaches, and unauthorized privilege escalation."
    )
    doc.add_paragraph(
        "To satisfy the core information assurance mandates, the system integrates a multi-layered cryptographic pipeline "
        "featuring per-user 16-byte cryptographically secure salts, an isolated application pepper secret, and the memory-hard "
        "Argon2id hashing algorithm. Furthermore, user authentication is guarded by a mandatory Two-Step / Two-Factor "
        "Authentication (2FA) one-time passcode workflow, strict Role-Based Access Control (RBAC) isolating Administrator, "
        "President, and Captain privileges, and automated daily disaster recovery backups via mysqldump."
    )

    add_callout(
        "Live Production & Source Deliverables:\n"
        "• Source Code Repository: https://github.com/OTI-BOATENG/gfa-system\n"
        "• Live Cloud Application: https://gfa-system-seven.vercel.app\n"
        "• Default Demo Users: admin, president, captain (and nanaquesi0206@gmail.com) with password AdminPass123!",
        "OFFICIAL SUBMISSION LINKS"
    )

    # 2. CIA TRIAD EXPLANATION
    add_h1("2. Information Assurance: The CIA Triad Architecture")
    doc.add_paragraph(
        "The classical CIA Triad (Confidentiality, Integrity, and Availability) forms the foundational benchmark for evaluating "
        "system security posture. The GFA system systematically addresses each facet through tailored technical countermeasures:"
    )

    add_h2("2.1 Confidentiality")
    doc.add_paragraph(
        "Confidentiality ensures that sensitive identity and operational records remain strictly inaccessible to unauthorized entities, "
        "eavesdroppers, or hostile actors. In the GFA System, confidentiality is enforced through four primary mechanisms:"
    )
    p_c1 = doc.add_paragraph()
    p_c1.add_run("1. High-Entropy Credential Protection (Salt & Pepper with Argon2id): ").bold = True
    p_c1.add_run(
        "Passwords are never stored in plaintext. They undergo concatenation with a global server-side pepper and a 16-byte "
        "per-user dynamic salt before being transformed by the memory-hard Argon2id algorithm. Even if attackers obtain a raw "
        "database dump, hashes cannot be reversed without the isolated server pepper."
    )
    p_c2 = doc.add_paragraph()
    p_c2.add_run("2. Mandatory Two-Factor Authentication (2FA): ").bold = True
    p_c2.add_run(
        "A compromised or intercepted password is insufficient to breach the system. A secondary, single-use 6-digit cryptographic "
        "token with a strict 5-minute lifespan must be verified before any session token is granted."
    )
    p_c3 = doc.add_paragraph()
    p_c3.add_run("3. Session Isolation & Principle of Least Privilege: ").bold = True
    p_c3.add_run(
        "Sessions store minimal identifiers (user ID, username, role name) rather than passwords or personal data. Role-based guards "
        "prevent unauthorized access to operational dashboards."
    )
    p_c4 = doc.add_paragraph()
    p_c4.add_run("4. Cryptographic Session Signing: ").bold = True
    p_c4.add_run(
        "Session identifiers and payload metadata are secured using HMAC-SHA256 signatures, ensuring session tokens cannot be "
        "forged, modified, or hijacked across distributed serverless environments."
    )

    add_h2("2.2 Integrity")
    doc.add_paragraph(
        "Integrity guarantees that data and state remain authentic, consistent, and unaltered by unauthorized parties or technical malfunctions. "
        "The GFA system implements robust integrity controls:"
    )
    p_i1 = doc.add_paragraph()
    p_i1.add_run("1. Parameterized Prepared Statements (SQLi Prevention): ").bold = True
    p_i1.add_run(
        "Every database interaction utilizes PHP Data Objects (PDO) prepared statements with parameterized inputs. SQL commands "
        "and user data are strictly bifurcated, neutralizing SQL Injection (SQLi) attacks across all registration, login, and dashboard routes."
    )
    p_i2 = doc.add_paragraph()
    p_i2.add_run("2. Relational Foreign Key Integrity & Cascading Constraints: ").bold = True
    p_i2.add_run(
        "Relational schemas enforce strict foreign key constraints (e.g. FOREIGN KEY (role_id) REFERENCES roles(id) and "
        "ON DELETE CASCADE on 2FA records), preventing orphaned records, inconsistent states, or referential corruption."
    )
    p_i3 = doc.add_paragraph()
    p_i3.add_run("3. Role-Based Access Control (RBAC) Enforcement: ").bold = True
    p_i3.add_run(
        "Mutation endpoints (adding/deleting players, appointing executives) verify role capabilities server-side on every request, "
        "ensuring captains or guests cannot spoof administrative modification commands."
    )

    add_h2("2.3 Availability")
    doc.add_paragraph(
        "Availability ensures authorized personnel have uninterrupted, prompt access to vital association records and operational tooling. "
        "The architecture secures availability through:"
    )
    p_a1 = doc.add_paragraph()
    p_a1.add_run("1. Automated Disaster Recovery Hot Backups: ").bold = True
    p_a1.add_run(
        "The system incorporates an automated backup engine (scripts/backup.php) that generates full mysqldump database snapshots "
        "with ISO-standard timestamps (e.g., gfa_backup_YYYYMMDD_HHMMSS.sql)."
    )
    p_a2 = doc.add_paragraph()
    p_a2.add_run("2. Database Backup Audit Logging: ").bold = True
    p_a2.add_run(
        "Every created snapshot is recorded with timestamp metadata in the backups database table for auditing, tracking, and compliance."
    )
    p_a3 = doc.add_paragraph()
    p_a3.add_run("3. Resilient Multi-Driver Database Architecture: ").bold = True
    p_a3.add_run(
        "The database layer in src/db.php features an automated fallback to an embedded SQLite database in /tmp when MySQL is "
        "unavailable or deployed on stateless cloud hosts like Vercel, preventing single-point-of-failure downtimes."
    )

    # CIA Summary Table
    cia_tbl = doc.add_table(rows=1, cols=3)
    cia_headers = ["Pillar", "Threat Vector Mitigated", "Technical Implementation in GFA System"]
    cia_data = [
        ["Confidentiality", "Credential theft, rainbow table attacks, offline database breach cracking", "Per-user 16-byte dynamic salts, global server pepper, Argon2id hashing, and mandatory 2FA OTP verification."],
        ["Integrity", "SQL injection, parameter tampering, unauthorized record manipulation", "PDO parameterized queries, strict role-based capability validation, foreign keys with ON DELETE CASCADE."],
        ["Availability", "Database corruption, server failures, accidental data loss, cloud outages", "Automated mysqldump hot backups, audit logging in backups table, and zero-downtime serverless SQLite fallback."],
    ]
    style_table(cia_tbl, [Inches(1.5), Inches(2.2), Inches(2.8)], cia_headers, cia_data)

    # 3. TWO-FACTOR AUTHENTICATION
    add_h1("3. Two-Step / Two-Factor Authentication (2FA) Architecture")
    doc.add_paragraph(
        "Two-Factor Authentication is an essential defensive countermeasure that mitigates credential stuffing, phishing, "
        "and password reuse vulnerabilities. In the GFA System, authentication is segmented into two sequential gates:"
    )

    add_h2("3.1 Step 1: Primary Credential Validation")
    doc.add_paragraph(
        "The user enters their username (or registered email) and plaintext password on public/index.php. The server retrieves "
        "the user record, extracts their unique salt, and verifies the candidate string against the stored Argon2id hash using "
        "verifyStoredPassword(). If validation fails, the attempt is terminated with a secured redirect."
    )

    add_h2("3.2 Step 2: One-Time Passcode (OTP) Generation & Dispatch")
    doc.add_paragraph(
        "Upon successful primary authentication, the system enters the secondary verification phase:"
    )
    doc.add_paragraph(
        "1. Token Generation: A cryptographically secure 6-digit numeric OTP is produced using random_int(0, 999999) "
        "and padded to ensure consistent 6-character length (e.g., '049281')."
    )
    doc.add_paragraph(
        "2. Cryptographic Storage: To prevent token leaks in case of an in-flight database dump, the OTP itself is never stored "
        "in plaintext. It is hashed using bcrypt (password_hash($code, PASSWORD_DEFAULT)) and inserted into the two_factor_codes table."
    )
    doc.add_paragraph(
        "3. Strict Time-To-Live (TTL): Each code is stamped with an expiration timestamp set to exactly 5 minutes "
        "(TWO_FACTOR_EXPIRY_MINUTES = 5). Any verification attempt made after expiration is rejected with '2FA code expired'."
    )
    doc.add_paragraph(
        "4. Academic Demo Badge: For evaluation and grading purposes, the generated OTP is conveniently displayed on the screen "
        "inside the secure 'Demo code' badge on auth/login.php."
    )

    add_h2("3.3 Step 3: Verification & Session Issuance")
    doc.add_paragraph(
        "When the user submits the code to auth/verify_2fa.php, the server executes strict validation:\n"
        "• It queries the latest active OTP for that specific user ID.\n"
        "• It verifies that the current timestamp is within the 5-minute validity window.\n"
        "• It executes password_verify($submitted_code, $stored_code_hash).\n"
        "• Once authenticated, the code is invalidated, the user session $_SESSION['user'] is established, an HMAC-signed "
        "authentication cookie is issued, and the user is redirected to their authorized dashboard."
    )

    add_callout(
        "Two-Factor Authentication Flow Summary:\n"
        "User Input (Username + Password) ➔ Argon2id Verification ➔ Generate 6-Digit OTP ➔ Hash via Bcrypt ➔ "
        "Store with 5-Minute TTL ➔ User Submits Code ➔ Bcrypt Hash Verification ➔ Invalidate OTP ➔ Issue Role Session",
        "2FA WORKFLOW PIPELINE"
    )

    # 4. SALT AND PEPPER
    add_h1("4. Cryptographic Architecture: Salt and Pepper Implementation")
    doc.add_paragraph(
        "Password protection in the GFA System is engineered against offline brute-force cracking, precomputed rainbow table "
        "attacks, and catastrophic database leaks. This is accomplished by combining per-user dynamic salts with an isolated application pepper."
    )

    add_h2("4.1 Salt vs. Pepper Theoretical Comparison")
    sp_tbl = doc.add_table(rows=1, cols=3)
    sp_headers = ["Attribute", "Per-User Dynamic Salt", "Application-Wide Pepper Secret"]
    sp_data = [
        ["Storage Location", "Stored directly alongside user records in the MySQL users.salt database column.", "Stored strictly outside the database in server environment variables or src/config.php."],
        ["Scope", "Unique to every registered user account (16 random bytes).", "Universal application secret shared across all credential candidate evaluations."],
        ["Primary Security Objective", "Thwarts rainbow table attacks. Ensures two identical passwords produce completely different hash outputs.", "Thwarts database breach exfiltration. If attackers dump the database, they cannot crack hashes without the isolated pepper."],
        ["Entropy Source", "Cryptographically secure pseudo-random bytes generated via random_bytes(16).", "High-entropy server secret string (e.g., 'GFA-DEMO-2026-Pepper')."],
        ["Visibility to Attackers", "Exposed if the database table is exfiltrated.", "Remains hidden even if full SQL database dumps are publicly leaked."],
    ]
    style_table(sp_tbl, [Inches(1.8), Inches(2.3), Inches(2.4)], sp_headers, sp_data)

    add_h2("4.2 Candidate Construction & The Argon2id Algorithm")
    doc.add_paragraph(
        "Before applying the hashing function, the system constructs a combined candidate string using a deterministic pipeline:"
    )
    doc.add_paragraph(
        "Candidate String = Application_Pepper || Plaintext_Password || Per_User_Salt"
    )
    doc.add_paragraph(
        "This candidate string is processed by the state-of-the-art Argon2id algorithm, configured with memory-hard parameters:\n"
        "• Memory Cost (m): 65,536 KiB (64 Megabytes of RAM per hash evaluation)\n"
        "• Time Cost (t): 4 iterations across memory blocks\n"
        "• Parallelism (p): 1 thread\n"
        "Argon2id was selected because it combines Argon2d's resistance to GPU/ASIC hardware brute-forcing with Argon2i's resistance "
        "to cache-timing side-channel attacks, providing the highest level of cryptographic assurance recommended by OWASP and NIST."
    )

    add_h2("4.3 Pepper Key Rotation & Resilient Fallback Handling")
    doc.add_paragraph(
        "In enterprise deployments, security standards require periodic rotation of cryptographic keys. Changing an application pepper "
        "normally breaks all existing user passwords. To prevent this, the GFA System introduces an intelligent key fallback mechanism in src/config.php:"
    )
    doc.add_paragraph(
        "When verifyStoredPassword() executes, it first tests the candidate against the active primary pepper. If verification does not match, "
        "it sequentially tests against a whitelist of configured fallback peppers (PASSWORD_PEPPER_FALLBACKS). This enables seamless key rotation "
        "without invalidating user accounts or forcing administrative password resets."
    )

    # Live Database Proof Table
    add_h2("4.4 Live Database Cryptographic Evidence")
    db_tbl = doc.add_table(rows=1, cols=4)
    db_headers = ["User ID & Name", "Assigned Role", "Per-User Salt (16-Byte Hex)", "Argon2id Hash Preview"]
    db_data = [
        ["1 - admin", "Administrator", "4ea22e14e1ba48f22edcbf4bd05b0cd6", "$argon2id$v=19$m=65536,t=4,p=1$dXBKLzdQbzk4..."],
        ["2 - president", "President", "ffa94466c9c53b89b6543b9ecacf2fd7", "$argon2id$v=19$m=65536,t=4,p=1$SzE5SFB6LkM0..."],
        ["3 - captain", "Captain", "f954f61622315c299a3cd78acd049491", "$argon2id$v=19$m=65536,t=4,p=1$U2VyTldPeEFU..."],
        ["4 - OTI-BOATENG", "Administrator", "9b81e285a8efb489d8cd2f694a112871", "$argon2id$v=19$m=65536,t=4,p=1$b01aUlpFT05..."],
    ]
    style_table(db_tbl, [Inches(1.5), Inches(1.3), Inches(2.2), Inches(1.5)], db_headers, db_data)

    # 5. SCREENSHOTS & SYSTEM WALKTHROUGH
    add_h1("5. System Demonstration & Screenshot Walkthrough")
    doc.add_paragraph(
        "This section documents the live user interfaces, security checkpoints, and database states of the GFA Management Information System."
    )

    screenshots_dir = os.path.join(os.path.dirname(__file__), "screenshots")

    # Figure 1: Login
    add_image_figure(
        os.path.join(screenshots_dir, "01_login_page.png"),
        "Figure 1: GFA Portal Responsive Login Interface",
        "Figure 1 showcases the entry portal. User credentials submitted here are subjected to salt, pepper, and Argon2id verification with 1-click demo credential fillers."
    )

    # Figure 2: 2FA
    add_image_figure(
        os.path.join(screenshots_dir, "03_two_factor_auth.png"),
        "Figure 2: Two-Factor Authentication (2FA) OTP Verification Screen",
        "Figure 2 illustrates the secondary gate prompting for the 6-digit one-time code with strict 5-minute time-to-live enforcement."
    )

    # Figure 3: Admin Dashboard
    add_image_figure(
        os.path.join(screenshots_dir, "04_admin_dashboard.png"),
        "Figure 3: Administrator Operational Dashboard",
        "Figure 3 displays the administrator command center showing real-time statistics, player management, committee administration, and CRUD controls."
    )

    # Figure 4: President Dashboard
    add_image_figure(
        os.path.join(screenshots_dir, "05_president_dashboard.png"),
        "Figure 4: Executive President Dashboard",
        "Figure 4 demonstrates the executive leadership dashboard providing high-level membership tracking and governance controls."
    )

    # Figure 5: Captain Dashboard
    add_image_figure(
        os.path.join(screenshots_dir, "06_captain_dashboard.png"),
        "Figure 5: Team Captain Squad Management Dashboard",
        "Figure 5 displays the captain interface restricted strictly to active squad member rosters, ages, and match statuses with read-only permissions."
    )

    # Figure 6: Registration
    add_image_figure(
        os.path.join(screenshots_dir, "02_registration_page.png"),
        "Figure 6: User Account Registration & Vetting Portal",
        "Figure 6 illustrates the new account registration interface supporting dynamic salt generation, Argon2id password hashing, and departmental vetting."
    )

    # Figure 7: Password Recovery
    add_image_figure(
        os.path.join(screenshots_dir, "07_forgot_password.png"),
        "Figure 7: Password Recovery & Identity Verification Interface",
        "Figure 7 depicts the self-service password recovery portal allowing users to securely request credential reset workflows."
    )

    # Figure 8: Database Cryptographic State
    add_image_figure(
        os.path.join(screenshots_dir, "08_database_salt_pepper.png"),
        "Figure 8: Live MySQL Database Cryptographic State",
        "Figure 8 provides forensic database evidence illustrating individual 16-byte hex salts, Argon2id password hashes, and isolated server pepper configuration."
    )

    # 6. ROLE-BASED ACCESS CONTROL (RBAC) MATRIX
    add_h1("6. Role-Based Access Control (RBAC) Matrix")
    doc.add_paragraph(
        "Privilege separation is enforced strictly by session guards on every page request. The access matrix below defines role capabilities:"
    )
    rbac_tbl = doc.add_table(rows=1, cols=4)
    rbac_headers = ["System Capability / Action", "Administrator", "President", "Captain"]
    rbac_data = [
        ["View Portal Home & Login", "Allowed", "Allowed", "Allowed"],
        ["Authenticate via Salt + Pepper + 2FA", "Allowed", "Allowed", "Allowed"],
        ["Access Admin Dashboard (/pages/admin.php)", "Allowed", "Denied (302 Redirect)", "Denied (302 Redirect)"],
        ["Access President Dashboard (/pages/president.php)", "Denied (302 Redirect)", "Allowed", "Denied (302 Redirect)"],
        ["Access Captain Dashboard (/pages/captain.php)", "Denied (302 Redirect)", "Denied (302 Redirect)", "Allowed"],
        ["Register New Players / Members / Executives", "Allowed", "Denied", "Denied"],
        ["Delete Players / Members / Executives", "Allowed", "Allowed (Members/Execs)", "Denied"],
        ["View Squad Roster & Player Age Statistics", "Allowed", "Allowed", "Allowed"],
        ["Trigger Automated Disaster Recovery Backup", "Allowed", "Denied", "Denied"],
    ]
    style_table(rbac_tbl, [Inches(2.5), Inches(1.3), Inches(1.4), Inches(1.3)], rbac_headers, rbac_data)

    # 7. SECURITY TEST LOG
    add_h1("7. Security Verification Test Log")
    doc.add_paragraph(
        "The following empirical test suite was conducted across the local XAMPP environment and the Vercel production cloud deployment:"
    )
    test_tbl = doc.add_table(rows=1, cols=4)
    test_headers = ["Test ID", "Security Control", "Execution & Test Scenario", "Status"]
    test_data = [
        ["TC-01", "Salt & Pepper Hashing", "Register/seed users; verify unique 16-byte hex salt in users table and Argon2id hash combines salt + pepper.", "PASSED"],
        ["TC-02", "Credential Validation", "Login with valid demo credentials (admin / AdminPass123!); verifyStoredPassword() succeeds.", "PASSED"],
        ["TC-03", "Pepper Rotation Fallback", "Execute authentication against secondary fallback pepper in PASSWORD_PEPPER_FALLBACKS array.", "PASSED"],
        ["TC-04", "2FA Expiry Enforcement", "Submit 2FA OTP code after > 5 minutes TTL window; verify system rejects with '2FA code expired'.", "PASSED"],
        ["TC-05", "2FA Single-Use Security", "Attempt to replay a previously verified OTP token; system rejects token reuse.", "PASSED"],
        ["TC-06", "RBAC Horizontal Privilege Escalation", "Sign in as Captain and attempt direct GET request to /pages/admin.php; system terminates access and redirects.", "PASSED"],
        ["TC-07", "SQL Injection Neutralization", "Inject payload ' OR '1'='1 in login username field; PDO prepared statement neutralizes payload as literal string.", "PASSED"],
        ["TC-08", "Disaster Recovery Backup", "Execute php scripts/backup.php; verify mysqldump generates timestamped SQL file and inserts audit log.", "PASSED"],
    ]
    style_table(test_tbl, [Inches(0.9), Inches(1.8), Inches(3.0), Inches(0.8)], test_headers, test_data)

    # 8. CONCLUSION
    add_h1("8. Conclusion")
    doc.add_paragraph(
        "The Ghana Football Association (GFA) Management Information System provides a comprehensive, mathematically grounded "
        "demonstration of modern secure software engineering. By weaving the CIA Triad into every architectural tier—combining "
        "per-user salts, server pepper secrets, the memory-hard Argon2id algorithm, two-factor authentication, rigid role-based access "
        "control, and automated disaster recovery backups—the system offers robust protection against credential cracking, privilege escalation, "
        "data corruption, and unauthorized data exfiltration."
    )

    output_filename = "OTI-BOATENG SYSTEM REPORT.docx"
    doc.save(output_filename)
    print(f"Report successfully generated and saved to: {output_filename}")

if __name__ == "__main__":
    create_report()
