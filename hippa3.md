# ONE CLICK PT NOTES

## Documentation-Only PT EMR Go-Live Compliance Checklist

**Intended Use:** Outpatient Physical Therapy and Physical Therapy documentation associated with home-health practice
**Location:** California
**Functions Excluded:** Insurance billing, claims submission, clearinghouse transactions, payment processing, eligibility verification, e-prescribing

---

# A. PATIENT RECORD REQUIREMENTS — REQUIRED

☐ System creates a unique patient record.

☐ Patient record includes patient full name.

☐ Patient record includes date of birth.

☐ Patient record includes contact information.

☐ Patient record can contain relevant diagnoses.

☐ Patient record can contain referring provider information when applicable.

☐ Patient record can contain medical history.

☐ Patient record can contain precautions and contraindications.

☐ Patient record supports an **Initial Physical Therapy Evaluation**.

☐ Patient record supports **Re-evaluation**.

☐ Patient record supports **Daily Treatment/SOAP Notes**.

☐ Patient record supports **Progress Reports**.

☐ Patient record supports **Plan of Care**.

☐ Patient record supports modifications to the plan of care.

☐ Patient record supports **Discharge Summary**.

☐ Each service identifies the **date of service**.

☐ Each service identifies the **nature of the service provided**.

☐ Each service identifies the **name of the individual providing the service**.

☐ Each service identifies the individual's professional title or role.

☐ PT documentation can be electronically signed.

☐ PTA documentation can be electronically signed by the PTA who performed the treatment.

☐ PT aides and other personnel can be identified appropriately when their participation must be documented.

☐ Notes cannot be attributed to a provider who did not actually authenticate the note.

---

# B. ELECTRONIC SIGNATURES — REQUIRED

☐ Every clinician has an individual account.

☐ Electronic signature is tied to that clinician's authenticated account.

☐ Signature displays provider's name.

☐ Signature displays credentials, such as PT or PTA.

☐ Signature contains date.

☐ Signature contains time or reliable timestamp.

☐ User cannot electronically sign as another clinician.

☐ Shared signatures are prohibited.

☐ Copying another provider's electronic signature is prohibited.

☐ Signed record indicates clearly that it has been electronically signed.

Recommended workflow:

**DRAFT → COMPLETE → ELECTRONICALLY SIGNED → LOCKED**

---

# C. SIGNED-NOTE INTEGRITY — HIGH PRIORITY

☐ Signed notes cannot be silently overwritten.

☐ Original signed documentation remains preserved.

☐ Corrections after signature occur through an amendment/addendum process.

☐ Addendum identifies the author.

☐ Addendum records date and time.

☐ System links the addendum to the original record.

☐ Original content remains viewable.

☐ System prevents ordinary users from changing timestamps manually.

☐ System maintains a history of significant modifications.

☐ Administrator cannot secretly rewrite clinical records without an audit trail.

---

# D. USER ACCOUNTS — REQUIRED

☐ Every workforce member receives a unique username.

☐ No general shared account such as:

* FrontDesk
* Clinic
* Therapist
* PTA1
* Staff

☐ Passwords are securely stored using appropriate password hashing.

☐ Password complexity requirements exist.

☐ Password-reset process is secure.

☐ Account lockout or rate limiting exists after repeated failed attempts.

☐ User accounts can be disabled immediately.

☐ Former employees can be removed promptly.

☐ Administrator can see active users.

☐ Administrator can see disabled users.

☐ System records account creation.

☐ System records account deactivation.

---

# E. MULTI-FACTOR AUTHENTICATION — STRONGLY RECOMMENDED

☐ MFA available for administrators.

☐ MFA available for clinicians.

☐ MFA required for remote administrative access.

☐ MFA required for developer access to production where feasible.

☐ Recovery process for lost MFA devices is secure.

---

# F. ROLE-BASED ACCESS CONTROL — REQUIRED

Create separate roles such as:

**Owner/Super Administrator**

☐ Full administrative privileges.

**Physical Therapist**

☐ View assigned/authorized patients.
☐ Create evaluation.
☐ Create treatment notes.
☐ Create progress reports.
☐ Sign notes.
☐ Complete discharge documentation.

**Physical Therapist Assistant**

☐ Appropriate patient access.
☐ Create/sign PTA treatment documentation.
☐ Cannot improperly sign as the PT.

**PT Aide**

☐ Only minimum information necessary for assigned duties.

**Front Desk**

☐ Patient demographics and scheduling-related information as appropriate.
☐ No unrestricted ability to modify clinical documentation.

**Medical Records Personnel**

☐ Authorized retrieval/export functions.

**Developer/IT**

☐ No routine clinical access merely because they built the system.

---

# G. AUDIT LOGGING — REQUIRED / CRITICAL

The system should create a permanent audit record containing:

**USER + ACTION + PATIENT + DATE/TIME + RESULT**

Log at minimum:

☐ Login.

☐ Logout.

☐ Failed login.

☐ Password reset.

☐ Patient record created.

☐ Patient chart viewed.

☐ Patient information edited.

☐ Clinical note created.

☐ Clinical note edited.

☐ Clinical note signed.

☐ Addendum created.

☐ File uploaded.

☐ File downloaded.

☐ Record printed.

☐ Record exported.

☐ User created.

☐ User disabled.

☐ User role changed.

☐ Permissions changed.

☐ Administrative settings changed.

☐ Backup/restoration event where applicable.

☐ Record archived/deleted if deletion is permitted.

Audit records should identify:

☐ Username/user ID.

☐ Patient record involved.

☐ Action performed.

☐ Date.

☐ Time.

☐ Success/failure where relevant.

☐ IP/device/session information where appropriate.

☐ Audit logs cannot be modified by ordinary staff.

☐ Audit logs cannot be casually deleted by the developer.

☐ Administrator has a mechanism to review audit activity.

---

# H. ENCRYPTION — REQUIRED SECURITY CONTROL

## Data in Transit

☐ Website/application uses HTTPS.

☐ Current secure TLS configuration is implemented.

☐ Unencrypted HTTP access redirects or is blocked.

☐ APIs containing PHI use encrypted transport.

## Data at Rest

☐ Production database encrypted at rest.

☐ File/document storage encrypted.

☐ Backups encrypted.

☐ Encryption keys managed separately and securely.

☐ Sensitive credentials are not stored directly in source code.

☐ Production secrets are not publicly exposed.

---

# I. CLOUD ENVIRONMENT

Document exactly:

☐ Name of cloud provider.

☐ Physical/logical region where production data resides.

☐ Location of database.

☐ Location of file storage.

☐ Location of backups.

☐ Location of application logs.

☐ List of cloud administrators.

☐ List of developer accounts.

☐ Cloud provider supports the required health-data security configuration.

☐ Business Associate Agreement obtained when required.

☐ Cloud-provider security responsibilities documented.

When a cloud service creates, receives, maintains, or transmits ePHI on behalf of a HIPAA-covered entity or business associate, HHS treats the cloud provider as a business associate and requires an appropriate BAA. This applies even when the provider only stores encrypted ePHI.

---

# J. DEVELOPMENT VS PRODUCTION ENVIRONMENT — REQUIRED

Maintain two separate environments:

## DEVELOPMENT

☐ Fake/synthetic patients only.

☐ No real patient PHI.

☐ Used for programming and testing.

## PRODUCTION

☐ Contains actual patient records.

☐ Strict access control.

☐ Logging enabled.

☐ Backups enabled.

☐ Production credentials different from development credentials.

☐ Developer access limited.

☐ Production access granted only when necessary.

---

# K. DATABASE SECURITY

☐ Database cannot be accessed publicly from the internet unless specifically secured and justified.

☐ Database authentication required.

☐ Database uses strong credentials.

☐ Database accounts follow least-privilege principles.

☐ Database administrative credentials are restricted.

☐ Application account does not automatically have unnecessary administrative privileges.

☐ Database backup access is restricted.

☐ Sensitive data is not included unnecessarily in debug logs.

☐ Database queries are protected against injection attacks.

☐ Input validation implemented.

☐ Database software regularly patched.

---

# L. FILE AND ATTACHMENT SECURITY

System may allow storage of:

* Referrals
* Prescriptions
* Imaging reports
* Physician records
* Medical reports
* Authorizations
* Patient forms
* Other relevant documents

Requirements:

☐ Uploaded files stored securely.

☐ File access follows user permissions.

☐ Unauthorized direct links to files are prevented.

☐ File-upload type restrictions implemented.

☐ Malicious file-upload protections implemented.

☐ Filename manipulation/path traversal prevented.

☐ Downloads are logged.

☐ Files are included in backups.

☐ Files can be exported with the patient chart.

---

# M. BACKUP SYSTEM — REQUIRED

☐ Automated backups.

☐ Backup frequency documented.

☐ Database included.

☐ Patient attachments included.

☐ Configuration necessary for restoration included.

☐ Backups encrypted.

☐ Backup access restricted.

☐ More than one backup generation retained.

☐ Backup failure generates an alert.

☐ Administrator can confirm last successful backup.

### VERY IMPORTANT

A backup is not considered reliable merely because a file exists.

☐ Perform a real restoration test.

☐ Confirm that restored patient records open correctly.

☐ Confirm attachments restore.

☐ Confirm signed notes remain intact.

☐ Document restoration-test date.

---

# N. DISASTER RECOVERY

Create procedures for:

☐ Server failure.

☐ Database corruption.

☐ Accidental deletion.

☐ Ransomware.

☐ Internet outage.

☐ Cloud outage.

☐ Lost or stolen device.

☐ Compromised administrator account.

☐ Developer unavailable.

☐ Company/provider termination.

Document:

☐ Recovery procedure.

☐ Responsible person.

☐ Backup location.

☐ Emergency contact.

☐ Estimated recovery priorities.

---

# O. SYSTEM AVAILABILITY / EMERGENCY ACCESS

☐ Authorized personnel have procedures for obtaining needed records during system failure.

☐ Emergency access process documented.

☐ Temporary downtime documentation procedure established if appropriate.

☐ Downtime notes can later be incorporated into the official medical record.

---

# P. AUTOMATIC SESSION SECURITY

☐ Automatic logout/session timeout.

☐ User must reauthenticate after appropriate inactivity.

☐ Session token protected.

☐ Session terminates after logout.

☐ Old login sessions can be revoked.

☐ Administrator can terminate all sessions for a compromised user.

---

# Q. MOBILE/iPAD USE

If PTs use iPads or tablets:

☐ Device requires authentication.

☐ Device encryption enabled.

☐ Automatic screen lock.

☐ Remote device management considered.

☐ Patient data not permanently downloaded to the device unless necessary and secured.

☐ Lost/stolen device response procedure.

☐ Application logout available.

☐ PHI does not appear unnecessarily on lock-screen notifications.

---

# R. REMOTE ACCESS

If accessing records outside the clinic:

☐ Secure encrypted connection.

☐ MFA strongly recommended.

☐ Individual user accounts.

☐ Access logged.

☐ Public/shared computers prohibited unless specifically controlled.

☐ Users prohibited from saving PHI to personal devices unless authorized and protected.

---

# S. DEVELOPER ACCESS

Developer should NOT automatically have permanent unrestricted access to live PHI.

☐ Developer's production account is separate.

☐ Developer access is disabled by default where practical.

☐ Access is activated only when required.

☐ Developer access is logged.

☐ Developer cannot erase their activity log.

☐ Developer access is terminated when relationship ends.

☐ Developer signs appropriate confidentiality/security agreements.

☐ BAA used when developer's role constitutes a HIPAA business-associate relationship.

☐ Developer cannot copy the patient database for unrelated testing.

☐ Developer cannot use PHI in demonstration environments.

☐ Developer cannot use your PHI to train AI systems or other products without appropriate legal basis/authorization.

---

# T. SOFTWARE OWNERSHIP AND DATA OWNERSHIP

Contract should establish:

☐ Clinic/company owns its patient information.

☐ You retain unrestricted access to your records.

☐ Developer cannot hold patient information hostage.

☐ Developer cannot disable access because of a contract dispute without appropriate legal provisions.

☐ Developer cannot sell the database.

☐ Developer cannot reuse patient information.

☐ Developer cannot commercially exploit PHI.

☐ Developer cannot duplicate your proprietary software if your existing development contract prohibits that.

☐ Source code ownership/license rights clearly documented.

☐ Database credentials/control are not exclusively held by the developer.

☐ You maintain administrative control or recovery rights.

---

# U. DATA EXPORT / PORTABILITY — REQUIRED

The program should allow:

☐ Individual patient chart export.

☐ Individual note export.

☐ Complete-chart PDF export.

☐ Uploaded-document export.

☐ Bulk record export for migration.

☐ Structured database export where feasible.

☐ Export does not destroy original data.

☐ Export activities are logged.

☐ Records remain legible after export.

California patients have rights to inspect and obtain copies of their records, so retrieval cannot depend entirely on the continued availability of one programmer or one proprietary screen.

---

# V. CALIFORNIA RECORD RETENTION — REQUIRED

Configure the software so that records are not automatically destroyed prematurely.

**General PT rule: maintain patient records for at least 7 years following discharge.**

For unemancipated minors, maintain them for at least one year after the minor reaches age 18, and in no case less than seven years.

☐ Retention period configured.

☐ No automatic premature deletion.

☐ Archived patients remain searchable.

☐ Records remain readable throughout retention period.

☐ Attachments follow appropriate retention requirements.

---

# W. CALIFORNIA PATIENT ACCESS

System must allow the clinic to retrieve records promptly.

☐ Patient chart searchable.

☐ Records printable.

☐ Records exportable.

☐ Complete chart can be assembled.

☐ Attachments retrievable.

☐ Record-release activity can be documented.

California PTBC states that patients generally must be given copies within the applicable statutory timeframe after a proper written request.

---

# X. HIPAA SECURITY RISK ANALYSIS — REQUIRED IF HIPAA APPLIES

Before live PHI:

☐ Identify all systems containing ePHI.

☐ Identify all users with access.

☐ Identify external vendors.

☐ Identify cloud services.

☐ Identify mobile-device access.

☐ Identify backups.

☐ Identify potential threats.

☐ Identify vulnerabilities.

☐ Assess likelihood.

☐ Assess potential impact.

☐ Assign risk levels.

☐ Document mitigation.

☐ Assign responsible person.

☐ Document completion dates.

☐ Reassess periodically and when material system changes occur.

The risk analysis should include:

**Application
Database
Cloud server
Backups
Wi-Fi/network
Clinic computers
iPads
Remote access
Developers
Employees
File exports
Email
Third-party integrations**

---

# Y. SECURITY POLICIES — REQUIRED OPERATIONAL CONTROLS

Clinic should maintain written policies covering:

☐ User access.

☐ Password security.

☐ Workforce access.

☐ Terminated employees.

☐ Mobile devices.

☐ Remote access.

☐ Backups.

☐ Disaster recovery.

☐ Security incidents.

☐ PHI disclosure.

☐ Record release.

☐ Device disposal.

☐ Software updates.

☐ Developer/vendor access.

☐ Audit-log review.

---

# Z. SECURITY OFFICER

☐ Designate a person responsible for security.

Responsibilities include:

☐ Access control.

☐ Reviewing incidents.

☐ Risk analysis coordination.

☐ Vendor oversight.

☐ Security-policy implementation.

☐ Employee security training.

☐ Periodic system review.

---

# AA. INCIDENT RESPONSE

System and practice must be prepared for:

☐ Unauthorized login.

☐ Stolen password.

☐ Malware.

☐ Ransomware.

☐ Lost laptop/tablet.

☐ Wrong patient record accessed.

☐ Unauthorized record download.

☐ Developer compromise.

☐ Database leak.

☐ Cloud breach.

Create written procedure:

**DETECT → CONTAIN → PRESERVE EVIDENCE → INVESTIGATE → REMEDIATE → DOCUMENT → DETERMINE NOTIFICATION REQUIREMENTS**

---

# AB. VULNERABILITY TESTING — STRONGLY RECOMMENDED BEFORE GO-LIVE

Independent security professional should assess:

☐ Authentication.

☐ Authorization.

☐ User roles.

☐ Broken access controls.

☐ API security.

☐ Database exposure.

☐ SQL/database injection vulnerabilities.

☐ Cross-site scripting.

☐ Session security.

☐ File uploads.

☐ File downloads.

☐ Password reset.

☐ Encryption configuration.

☐ Server configuration.

☐ Cloud configuration.

☐ Administrative portal.

☐ Mobile/tablet access.

☐ Audit logging.

☐ Backup exposure.

☐ Known software vulnerabilities.

Results categorized:

**CRITICAL
HIGH
MEDIUM
LOW**

Before go-live:

☐ All critical findings resolved.

☐ High-risk findings resolved or formally mitigated.

☐ Security retest completed.

☐ Written report retained.

---

# AC. SOFTWARE UPDATE / PATCH MANAGEMENT

☐ Operating system patched.

☐ Database engine patched.

☐ Web framework maintained.

☐ Third-party libraries maintained.

☐ Vulnerable dependencies monitored.

☐ Security updates tested before deployment.

☐ Production update process documented.

☐ Ability to roll back failed software update.

---

# AD. CHANGE MANAGEMENT

Every production software update should record:

☐ What changed.

☐ Who made the change.

☐ Date of change.

☐ Version number.

☐ Testing performed.

☐ Security implications.

☐ Database migration performed, if applicable.

☐ Backup taken before major update.

---

# AE. CLINICAL NOTE TEMPLATES

## Initial Evaluation

☐ Patient information.

☐ Date of evaluation.

☐ Medical history.

☐ Subjective findings.

☐ Objective examination.

☐ Functional limitations.

☐ Diagnosis/clinical impression as appropriate.

☐ Prognosis.

☐ Assessment.

☐ Goals.

☐ Plan of care.

☐ Interventions planned.

☐ Provider identification.

☐ PT electronic signature.

## Daily Treatment Note

☐ Date.

☐ Subjective status.

☐ Objective findings/intervention.

☐ Treatment performed.

☐ Patient response.

☐ Assessment/progress.

☐ Plan.

☐ Provider name/title.

☐ Electronic signature.

## Reevaluation / Progress

☐ Relevant objective changes.

☐ Functional progress.

☐ Goal status.

☐ Assessment.

☐ Updated treatment plan where necessary.

☐ PT signature.

## Discharge

☐ Discharge date.

☐ Patient status.

☐ Goal status.

☐ Treatment outcome.

☐ Reason for discharge.

☐ Recommendations.

☐ PT signature.

---

# AF. HOME-HEALTH PT DOCUMENTATION

Software may be used as a PT documentation tool, but determine whether the specific home-health agency requires documentation in its own official EMR.

☐ Identify employing/contracting HHA.

☐ Identify HHA documentation requirements.

☐ Determine whether your system is permitted to serve as the official record.

☐ Avoid maintaining conflicting versions of an official patient record.

☐ Follow agency requirements when agency documentation is mandatory.

☐ Do not assume your independent EMR replaces the HHA's required record.

---

# AG. FUNCTIONS NOT REQUIRED FOR THIS VERSION

Because this is a documentation-only product, do **not** build these unless you later choose to:

☐ CMS-1500 claim generation — NOT REQUIRED.

☐ Insurance claim submission — NOT REQUIRED.

☐ Electronic clearinghouse — NOT REQUIRED.

☐ Eligibility verification — NOT REQUIRED.

☐ ERA/EOB processing — NOT REQUIRED.

☐ Credit-card processing — NOT REQUIRED.

☐ Insurance-payment posting — NOT REQUIRED.

☐ Claim scrubbing — NOT REQUIRED.

☐ Medicare electronic claims — NOT REQUIRED.

☐ Electronic prescribing — NOT REQUIRED.

☐ Pharmacy integration — NOT REQUIRED.

☐ Billing ledger — NOT REQUIRED.

---

# AH. PRE-GO-LIVE TEST

Do **not put real patient information into the system** until the following tests have been completed satisfactorily:

☐ User login test.

☐ Failed login test.

☐ MFA test if implemented.

☐ PT permission test.

☐ PTA permission test.

☐ Front-desk permission test.

☐ Administrator permission test.

☐ Terminated-user test.

☐ Audit-log test.

☐ Evaluation-signature test.

☐ Treatment-note signature test.

☐ Signed-note locking test.

☐ Addendum test.

☐ Unauthorized-edit test.

☐ Patient-chart export test.

☐ Complete-record PDF test.

☐ Attachment upload test.

☐ Attachment download test.

☐ Backup test.

☐ Full restoration test.

☐ Automatic logout test.

☐ Remote access test.

☐ Mobile/iPad test.

☐ Developer-access test.

☐ Database security review.

☐ Vulnerability/security assessment.

☐ Risk analysis completed.

☐ Required BAAs executed.

☐ Written policies completed.

---

# AI. FINAL GO-LIVE APPROVAL

### Developer Certification

Developer confirms that:

☐ Required functionality has been completed.

☐ Security controls have been implemented.

☐ Known critical vulnerabilities have been corrected.

☐ Production and development environments are separated.

☐ Production backups work.

☐ Audit logs work.

☐ Signed-record protections work.

☐ Export functions work.

Developer Name: ______________________________

Signature: ___________________________________

Date: _______________________________________

### Independent Security Reviewer

☐ Security review completed.

☐ Critical vulnerabilities corrected.

☐ High vulnerabilities appropriately addressed.

☐ Retest completed.

Reviewer: ___________________________________

Company: ____________________________________

Signature: ___________________________________

Date: _______________________________________

### Clinic/Owner Approval

☐ HIPAA Security Risk Analysis completed as applicable.

☐ Vendor/BAA review completed.

☐ Workforce policies implemented.

☐ Staff training completed.

☐ Backup restoration verified.

☐ Clinical documentation templates verified.

☐ California PT documentation requirements reviewed.

☐ System approved for LIVE PHI.

Owner/Administrator: __________________________

Signature: ___________________________________

Date: _______________________________________

# FINAL STATUS

☐ **NOT APPROVED FOR REAL PATIENT DATA**

or

☐ **APPROVED FOR LIVE PATIENT PHI**

Go-Live Date: _________________________________
