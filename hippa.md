If you are building a custom electronic medical record (EMR) solely for clinical documentation—PT evaluations, progress notes, daily treatment notes, discharge notes, patient demographics, attachments, etc.—there generally is not a California “EMR license” that you must obtain before your own physical therapy practice can use the software.

Also, federal ONC certification is generally voluntary; it becomes relevant when participation in a particular federal or other program requires Certified EHR Technology (CEHRT).

However, before you put real patient information into your custom system, I would not deploy it until you have completed a formal HIPAA/security readiness process. Once actual patient PHI/ePHI goes into it, your clinic remains legally responsible for protecting that information.

For your One Click PT Notes–type system

From what you're describing, the system would essentially be:

Patient → PT → Evaluation/Daily Notes/Progress/DC → Electronic Signature → Permanent Medical Record → Secure Storage/Backup

with no billing, claims submission, prescribing, pharmacy interface, or payment processing.

That simplifies the regulatory picture considerably.

The distinction I would make
Question	For your system
California license specifically to operate EMR software?	Generally no
FDA approval?	Generally no for ordinary documentation/recordkeeping software
ONC certification required merely to document PT visits?	Generally no
HIPAA compliance required when storing ePHI?	Yes
California privacy/medical-record laws?	Yes
Security risk analysis before deployment?	Yes — I strongly recommend before live PHI
BAA with cloud/storage vendors handling ePHI?	Yes, when they are business associates
BAA/appropriate agreement with developers who can access ePHI?	Yes, if they create, receive, maintain, or transmit ePHI on your behalf
Audit logging?	Yes
User authentication/access controls?	Yes
Backup/disaster recovery?	Yes
Ability to produce patient records?	Yes
PT documentation/retention requirements?	Yes

HHS specifically requires regulated entities to implement access controls, audit controls, integrity protections, authentication and transmission security for systems containing ePHI.

Checklist I would give your software engineer

Before allowing one real patient's information into the system, I would have the developer demonstrate/document all of the following.

1. USER ACCESS & AUTHENTICATION

☐ Every employee has an individual username/account
☐ No shared accounts such as "FrontDesk" or "Therapist1"
☐ Strong password requirements
☐ Multi-factor authentication (MFA), especially for administrators and remote access
☐ Automatic session timeout
☐ Account lockout/rate limiting after repeated failed logins
☐ Ability to immediately deactivate terminated employees
☐ Secure password reset/recovery
☐ Administrator accounts separated from ordinary clinical accounts

2. ROLE-BASED ACCESS

You should be able to establish different permissions for:

Administrator / PT / PTA / PT aide / front desk / medical records / developer

For example, your receptionist shouldn't automatically have the same privileges as you as the owner/administrator.

HHS requires appropriate authorization and access management for workforce members who work with ePHI.

3. AUDIT LOG — THIS IS CRITICAL

Your software should record:

WHO → DID WHAT → TO WHICH PATIENT → WHEN → FROM WHERE/DEVICE (as appropriate)

At minimum:

☐ Login
☐ Logout
☐ Failed login attempts
☐ Patient chart opened/viewed
☐ Patient created
☐ Record created
☐ Record edited
☐ Record signed
☐ Record printed
☐ Record downloaded/exported
☐ Attachment uploaded/downloaded
☐ User permissions changed
☐ Record deleted/archived, if permitted
☐ Administrative activity

And ordinary users should not be able to erase the audit trail.

HIPAA expressly requires mechanisms to record and examine activity in systems containing ePHI.

4. DOCUMENT INTEGRITY

This is especially important for medical documentation.

Once I sign a PT evaluation, for example, an employee should not simply be able to rewrite it later without leaving evidence of the change.

I would design:

Draft → Completed → Electronically Signed → Locked

If something needs correction:

Signed Original → Addendum/Correction → New timestamp + author

Preserve the original.

Do not silently overwrite it.

HIPAA requires safeguards against improper alteration or destruction of ePHI.

5. ELECTRONIC SIGNATURE

Each clinical note should capture at least:

Provider name
Credentials
Date
Time
Electronic signature/authentication

Example:

Electronically signed by:
Brian Masayon, PT
09/04/2026 – 3:42 PM

The signature should be associated with that specific authenticated user—not simply an image anyone can paste into the record.

6. ENCRYPTION

I would require:

☐ Encryption in transit using current secure TLS
☐ Encryption at rest for databases/storage
☐ Encrypted backups
☐ Secure key management
☐ No PHI stored in ordinary unencrypted logs
☐ No PHI accidentally exposed through URLs, debugging tools, analytics, crash logs, etc.

7. BACKUPS AND DISASTER RECOVERY

Don't just ask your developer:

"Do we have backups?"

Require evidence that restoration actually works.

I would require:

☐ Automated backups
☐ Offsite/redundant backup
☐ Encrypted backup
☐ Defined backup frequency
☐ Defined retention/versioning
☐ Restore procedure
☐ Periodic restoration testing
☐ Disaster-recovery plan
☐ Emergency access procedure
☐ Documentation showing last successful backup and last successful restore test

8. CLOUD PROVIDER

If your engineer uses a cloud provider, determine exactly:

Where is the database?
Where are uploaded documents?
Where are backups?
Who has access?
Where are logs?

Most importantly, obtain the appropriate Business Associate Agreement from any cloud service provider that creates, receives, maintains or transmits your ePHI.

HHS explicitly says a covered entity may use a cloud provider for ePHI, but a HIPAA-compliant BAA is required when the cloud provider is acting as its business associate.

9. YOUR SOFTWARE ENGINEER

This deserves special attention.

Your developer should not have unrestricted permanent access to your live patient database just because they wrote the software.

I would establish:

Development environment — fake/test patients only

Production environment — actual PHI

Developer access to production should be restricted, logged, controlled and only available when genuinely required.

Because your developer may potentially maintain or access ePHI on your behalf, the business-associate relationship needs to be properly addressed.

10. HIPAA SECURITY RISK ANALYSIS

This is one of the biggest items.

Before going live, perform and document a HIPAA Security Risk Analysis (SRA) of the entire system.

HHS says regulated entities must conduct an accurate and thorough assessment of potential risks and vulnerabilities to ePHI and then implement measures reducing those risks to reasonable and appropriate levels.

That analysis should examine:

Server → Database → Application → Users → Computers → iPads → Internet → Remote access → Backups → Developer → Cloud provider → Email/export → Attachments

Then produce a remediation plan for vulnerabilities.

11. PENETRATION/VULNERABILITY TESTING

This isn't the same thing as HIPAA compliance, but for a custom-built EMR I would have an independent cybersecurity professional perform testing before production.

At minimum:

☐ Vulnerability scan
☐ Web-application security assessment
☐ Authentication testing
☐ Authorization/privilege escalation testing
☐ API security testing
☐ Database exposure testing
☐ Session-management testing
☐ File upload/download testing
☐ Encryption/configuration review
☐ OWASP-style application security testing
☐ Remediation report
☐ Retest after critical/high vulnerabilities are fixed

Your software engineer should not be the only person certifying that his own system is secure.

12. INCIDENT RESPONSE

Build procedures for:

Suspected breach → contain → investigate → preserve logs → determine affected records → remediate → evaluate notification obligations

Your system should provide enough logs to determine what happened.

13. PATIENT RECORD EXPORT

This is especially important for you.

You should never become trapped inside software that you own.

Require:

☐ Complete patient-chart PDF
☐ Individual note PDF
☐ Attachments downloadable in original format
☐ Bulk export capability
☐ Database/data export
☐ Export audit trail
☐ Ability to provide records to patients
☐ Ability to migrate your data to another EMR

California PT patients have statutory rights to inspect and obtain copies of their records, and PTBC describes applicable response requirements.

14. RECORD RETENTION

Your system needs to support California's retention requirements.

The California Physical Therapy Board states that PT patient records must generally be maintained for at least seven years following discharge, with additional rules applicable to unemancipated minors.

Therefore don't program the system to automatically destroy a chart after two or three years.

15. PHYSICAL THERAPY DOCUMENTATION

The software should support, at minimum, your clinical workflow:

Initial Evaluation

Demographics
Referral/prescription when applicable
Medical history
Diagnosis
Precautions
Subjective examination
Objective examination
ROM
Strength
Pain
Functional limitations
Assessment
Goals
Plan of care
Frequency/duration
PT signature/date

Daily Treatment

Date of service
Subjective
Objective
Interventions
Timed/untimed treatment information as applicable
Response to treatment
Assessment
Plan
Provider/signature

Progress/Reevaluation

Objective change
Goal status
Functional progress
Assessment
Updated plan

Discharge

Status
Goals achieved/not achieved
Final assessment
Reason for discharge
Recommendations

And because you want to use it for both outpatient and home-health PT, don't assume one documentation template will automatically satisfy every payer, home-health agency or Medicare contractual requirement.

One issue I would change in your original plan

You mentioned eventually using this for Home Health Physical Therapy.

There is an important distinction:

Scenario A — Your own outpatient clinic

You're using your custom software as your clinic's internal medical-record/documentation system.

That's relatively straightforward.

Scenario B — You work for a Home Health Agency

If the home health agency requires its documentation to reside in its designated EMR, your independent system generally shouldn't replace that agency's official record unless the agency has approved/integrated the workflow.

Scenario C — Your software eventually becomes a commercial product

If One Click PT Notes LLC eventually sells/subscribes/licenses this system to other PT clinics, the compliance picture becomes considerably more involved.

You would effectively become a health-IT vendor/business associate handling ePHI for customers.

That means I would build the software now as though it will eventually become commercial rather than trying to retrofit security afterward.

Do you need ONC certification?

For what you've described right now, probably not.

ONC describes its Health IT Certification Program as a voluntary certification program, although certified technology is required by certain CMS and other programs.

Therefore I would not spend money pursuing ONC certification simply because you want to use a custom PT documentation system, unless you identify a specific program, payer, interoperability requirement, or future commercial strategy that requires CEHRT.

The go-live standard I recommend

I would divide development into four gates:

GATE 1 — SOFTWARE COMPLETE

Developer finishes functionality.

↓

GATE 2 — HIPAA/LEGAL READINESS

BAAs
Privacy/security policies
HIPAA Security Risk Analysis
Access-control review
California requirements review

↓

GATE 3 — INDEPENDENT SECURITY VALIDATION

Vulnerability/security testing
Critical/high findings corrected
Backup restoration test
Audit-log test
Permission/access test
Incident-response test

↓

GATE 4 — LIVE PHI

Only after those are completed would I begin entering actual patient information.

Until Gate 4, tell your engineer to use synthetic/fake patient data only.

One Click PT Notes — minimum production architecture

User → MFA → Role-Based Access → EMR Application → Encrypted Database

with:

Audit Log + Immutable Signed Notes + Encrypted Backup + Disaster Recovery + Patient Export + Security Monitoring

That is the architecture I would build toward.

And one final distinction is important: "HIPAA compliant" is not a one-time government certificate that someone gives your software. HIPAA requires an ongoing combination of technical safeguards, administrative safeguards, policies, risk analysis, workforce controls, vendor agreements and continued security management. HHS specifically requires periodic review and updating of safeguards and documentation.

HHS HIPAA Security Rule guidance
ONC Health IT Certification Program
California Physical Therapy Board medical-record guidance

Given that you're actively having the software built, the most useful next step would be to turn this into a formal "ONE CLICK PT NOTES — HIPAA/EMR SOFTWARE GO-LIVE COMPLIANCE CHECKLIST" that you can hand directly to your software engineer, with roughly 75–100 individual requirements and columns for Required / Developer Completed / Independently Tested / Evidence Provided / Date / Comments.