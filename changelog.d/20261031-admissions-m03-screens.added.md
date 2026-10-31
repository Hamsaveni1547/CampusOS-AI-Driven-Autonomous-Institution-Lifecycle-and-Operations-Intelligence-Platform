Add the M03 Admissions screens.

- Applicants (`/my-applications`): open cycles with programmes, quotas and seats left; apply; fill personal, guardian, academic and entrance details; upload each required document; save draft; submit; see corrections asked for; accept or decline an offer; withdraw; see the register number once admitted.
- Admissions desk (`/applications`): filters by cycle, programme, quota and status with counts per status; application page with documents (verify or reject with a reason), ask for corrections, complete verification, management-quota approval, reject, offer a seat, and confirm admission by recording the fee receipt.
- Admission cycles (`/admission-cycles`): create and edit cycles (dates, application fee, offer validity, required documents, instructions), seat matrix per programme and quota with seats used, open and close the cycle.
- Quotas, statuses and categories come from the API (`GET admissions/options`), not from the frontend.
