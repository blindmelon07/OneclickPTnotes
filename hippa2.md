Correct. That substantially narrows the scope.

If your software will only document and store patient records—and will not submit insurance claims, perform billing, process payments, transmit claims to Medicare/Medicaid, perform e-prescribing, or interface with clearinghouses—then you do not need to build a certified billing/EHR platform simply because you are keeping PT records electronically.

California law expressly allows healthcare providers to maintain patient records electronically.

What your system actually needs to be

Think of it as a secure electronic clinical documentation/medical-record system, not a billing system.

For your intended use, I would focus the development requirements on:

Patient demographics and medical information
PT initial evaluation
Daily treatment/SOAP notes
Progress reports/reevaluations
Discharge summaries
Home-health PT documentation, when appropriate
Uploading referrals, prescriptions, imaging/reports, etc.
PT/PTA identification and electronic signatures
Date/time stamps
Locking completed/signed notes
Addenda/corrections without destroying the original
Role-based user permissions
Audit logs showing who viewed/changed/exported records
Secure authentication/MFA
Encryption
Secure backups and disaster recovery
Patient-record PDF/export capability
Appropriate record retention

California PT records generally must be maintained for at least seven years following discharge, with special rules for unemancipated minors. Patients also have statutory rights to inspect and obtain copies of their records, so your software should make retrieval/export straightforward.

The key issue is PHI security—not billing

Even though you're doing zero insurance billing, once your system contains identifiable electronic patient health information, you need to address the applicable privacy and security requirements.

For a HIPAA-regulated practice, HHS requires safeguards covering access control, authentication, audit controls, integrity, transmission security, risk analysis, workforce access and other protections for ePHI.

So “we don't bill insurance through the software” does not make the patient information exempt from HIPAA security requirements.

For example:

John Smith
DOB: 01/01/1970
Diagnosis: Low back pain
PT Evaluation
ROM, strength, pain level, functional limitations
Treatment performed
PT assessment

That's health information you need to protect regardless of whether a claim is ever generated from the software.

You probably do NOT need

Based on the limited documentation-only function you've described, I would not make these part of your initial build unless you later need them:

Insurance claim submission • CMS-1500 generation • clearinghouse integration • ERA/EOB • insurance eligibility verification • payment processing • CPT billing engine • ICD billing validation • Medicare claim transmission • electronic prescribing • pharmacy interface.

That will make your software considerably simpler.

But before putting real patients into it

I would still require four things:

1. HIPAA Security Risk Analysis — formally assess how the system creates, stores, accesses, transmits, backs up and protects ePHI. HHS specifically requires regulated entities to assess risks and vulnerabilities to ePHI.

2. Independent security review/testing — have someone other than the programmer examine authentication, database security, APIs, access permissions, encryption, backups, vulnerabilities and audit logging.

3. BAAs where required — particularly with a cloud provider that maintains your ePHI. HHS specifically says a cloud provider maintaining ePHI is a business associate even if the information is encrypted and the cloud company does not possess the decryption key.

4. Written go-live validation — document that access controls, audit trails, backup restoration, record locking, electronic signatures, exports and other critical functions actually work before introducing live PHI.

So I would not look for a government agency to “inspect and approve” your documentation software before you use it. Instead, you need to establish that your electronic-record environment complies with the privacy, security, medical-record and PT-practice requirements that apply to your practice.

For your specific project, the next thing I recommend is creating a developer-facing checklist specifically for a documentation-only PT EMR—removing all unnecessary billing/insurance requirements. I can make it very detailed, with REQUIRED / RECOMMENDED / NOT NEEDED, so you can hand it directly to your software engineer.