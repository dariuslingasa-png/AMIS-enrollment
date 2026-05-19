# Teacher Feedback After Report

## Summary
These are improvement suggestions gathered after presenting the enrollment system. Use this as the next implementation checklist.

## 1. Missing Document Alternative
If an applicant does not have an available required document, allow them to upload or submit an affidavit instead.

### Notes
- Add an affidavit option per required document.
- The system should clearly explain when an affidavit is acceptable.
- Admin should still be able to approve or reject the submitted affidavit.

## 2. OLD Student Handling
Clarify and improve the flow for OLD AMIS students.

### Notes
- Check what fields are still required for OLD students.
- Avoid asking OLD students to re-enter information already on record, if possible.
- Confirm if OLD students still need to upload all documents.

## 3. Multiple Students Per Parent Account
Support parents enrolling multiple students using one parent account or Gmail.

### Notes
- A parent account should be able to create multiple enrollment applications.
- Dashboard should show a list of children/applications.
- Each student should have separate status, documents, and payment tracking.

## 4. Country Others and Country Code
Improve country/contact fields.

### Notes
- Add `Others` option in the Country field.
- Add country code support for contact numbers.
- Consider separate fields for country code and phone number.

## 5. Time Zone, Shift Slots, and Limited Slot Disclaimer
Add clearer disclaimer and logic for online class schedule and limited slots.

### Notes
- Show a time zone disclaimer for applicants outside the Philippines.
- If one shift/slot is full, automatically guide applicants to the available shift.
- Example: Grade 1 has 2 sections only, 40 max slots. If first shift is full, next students should be placed in second shift if available.
- Need slot rules by grade level, section count, and shift.

## 6. Required vs Optional Fields Review
Double-check all enrollment fields and document uploads.

### Notes
- Confirm which fields are required.
- Confirm which fields are optional.
- Adjust frontend validation and backend validation together.

## 7. Status, Religion, and Ethnicity Fields
Review demographic/status fields.

### Notes
- Clarify if `Status` means student status, civil status, or applicant status.
- Add or verify Religion field.
- Add Ethnicity field options such as Cebuano, Arabian, and Others.

## 8. Student Medical History
Improve the medical history section.

### Notes
- Make the medical section more specific and useful.
- Add fields for allergies, medication, health conditions, emergency instructions, and physician details if required.
- Confirm which medical fields are required.

## 9. Data Privacy, Referral, and Agreement Placement
Move Data Privacy to the referral side and move the agreement to the documentation side.

### Notes
- Data Privacy should be grouped near referral or applicant consent details.
- Agreement should be near the document upload/final submission step.
- Include non-refundable enrollment fee agreement.

## 10. Non-Refundable Enrollment Fee Agreement
Add a required agreement checkbox for the enrollment fee.

### Suggested Meaning
The applicant understands that the enrollment fee is non-refundable once paid, even if the application is later rejected due to incomplete, invalid, or unqualified documents.

### Notes
- This should be required before payment submission or final enrollment submission.
- Admin may reject an application even if payment was already made.
- The agreement should be explicit and easy to understand.

## 11. Recent or Annual 2x2 Picture Guide
Improve the 2x2 picture requirement.

### Notes
- Require a recent or annual student picture.
- Add a 2x2 guide with a sample image.
- Show clear instructions: plain/white background, front-facing, no filters, clear face, correct size.

## Suggested Priority
1. Required/optional field audit
2. Non-refundable fee agreement
3. Multiple students per parent account
4. Affidavit option for missing documents
5. Country code and Others option
6. Medical history improvements
7. Shift slot and time zone logic
8. 2x2 photo guide
