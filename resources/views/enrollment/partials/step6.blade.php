<section class="space-y-5">
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:gap-6">
        <x-upload-requirement-card
            title="Recent or Annual 1:1 Ratio"
            description="Please provide a recent clear student photo for admissions review."
            name="photo_2x2"
            :required="true"
            :uploaded="$applicant?->photo_2x2_url"
            accept="image/jpeg,image/jpg,image/png"
            :maxSizeMB="2"
            guide-title="Photo guide"
            :guide="[
                'Recent or annual student picture.',
                'Plain white background, front-facing, clear face.',
                'Hijab color: white for elementary students; black for high school students.',
                'No filters, heavy shadows, or hats.',
            ]"
            guide-notice="For niqab-wearing students, please wear hijab for the photo and follow the hijab photo guide. A female staff member or female admin will review it respectfully when privacy is needed."
            guide-notice-gender="Female"
            :support-panel-groups="[
                'Female' => [
                    ['src' => 'images/2x2-guide/non-hijab-guidelines.webp', 'label' => 'Non-hijab guidelines', 'alt' => 'Non-hijab photo guidelines for elementary and high school students'],
                    ['src' => 'images/2x2-guide/hijab-guidelines.webp', 'label' => 'Hijab guidelines', 'alt' => 'Hijab photo guidelines for elementary and high school students'],
                ],
                'Male' => [
                    ['src' => 'images/2x2-guide/boys-guidelines.webp', 'label' => 'Boys guidelines', 'alt' => 'Boys photo guidelines for elementary and high school students'],
                ],
            ]"
            :show-photo-sample="true"
        />

        <x-upload-requirement-card
            title="Photocopy Birth Certificate"
            description="Upload a readable photocopy of the student birth certificate."
            name="birth_cert"
            :required="false"
            :uploaded="$applicant?->birth_cert_url"
            accept="image/jpeg,image/jpg,image/png,application/pdf"
            :maxSizeMB="10"
            guide-title="Preparation note"
            :guide="[
                'Upload the birth certificate copy with the student name readable.',
                'Upload a clear JPG or PNG image.',
                'If the original is not yet available, you may upload an affidavit below.',
            ]"
            :guide-images="[
                ['src' => 'images/document-guide/birth-cert-blurry.svg', 'label' => 'Blurry', 'tone' => 'danger', 'alt' => 'Blurry birth certificate upload example'],
                ['src' => 'images/document-guide/document-cropped.svg', 'label' => 'Cropped', 'tone' => 'danger', 'alt' => 'Cropped birth certificate upload example'],
                ['src' => 'images/document-guide/birth-cert-correct.svg', 'label' => 'Correct', 'tone' => 'success', 'alt' => 'Correct readable birth certificate upload example'],
            ]"
        />

        <div x-show="form.student_type !== 'Old' && !isKinderOrNursery()" x-cloak>
            <x-upload-requirement-card
                title="Official Transcript / Report Card"
                description="Upload the student's report card, transcript, or a signed temporary affidavit."
                name="report_card"
                :required="true"
                :uploaded="$applicant?->report_card_url ?: $applicant?->affidavit_url"
                accept="image/jpeg,image/jpg,image/png,application/pdf"
                :maxSizeMB="5"
                guide-title="Preparation note"
                :guide="[
                    'Latest report card or transcript copy.',
                    'Make sure grades and school name are visible.',
                    'Upload a flat, uncropped image or PDF for faster review.',
                ]"
                :guide-images="[
                    ['src' => 'images/document-guide/document-blurry.svg', 'label' => 'Blurry', 'tone' => 'danger', 'alt' => 'Blurry report card upload example'],
                    ['src' => 'images/document-guide/document-cropped.svg', 'label' => 'Cropped', 'tone' => 'danger', 'alt' => 'Cropped report card upload example'],
                    ['src' => 'images/document-guide/document-correct.svg', 'label' => 'Correct', 'tone' => 'success', 'alt' => 'Correct readable report card upload example'],
                ]"
            >
            <div class="!mt-4 !rounded-xl !bg-slate-50 !p-4">
                <p class="!m-0 !text-sm !font-semibold !leading-6 !text-slate-800">No Report Card yet?</p>
                <div class="!mt-2 !space-y-1.5 !text-sm !leading-6 !text-slate-600">
                    <p class="!m-0">If the report card is not yet available, please:</p>
                    <ol class="!m-0 !list-decimal !space-y-1.5 !pl-5">
                        <li>Download the <a href="{{ asset('docs/Affidavit_enrollee.pdf') }}" target="_blank" class="!text-emerald-700 !font-semibold !underline">blank affidavit PDF</a>.</li>
                        <li>Print, fill out, and sign the document.</li>
                        <li>Upload the signed affidavit here in this card.</li>
                    </ol>
                </div>
            </div>
            </x-upload-requirement-card>
        </div>

        <x-upload-requirement-card
            title="Marriage Contract (Parents)"
            description="Optional supporting document if available or requested."
            name="marriage_contract"
            :uploaded="$applicant?->marriage_contract_url"
            accept="image/jpeg,image/jpg,image/png,application/pdf"
            :maxSizeMB="10"
            guide-title="Preparation note"
            :guide="[
                'Only upload if available or requested.',
                'A clear JPG or PNG photo is okay.',
                'Make sure the full page, names, and signatures are visible.',
            ]"
            :guide-images="[
                ['src' => 'images/document-guide/document-blurry.svg', 'label' => 'Blurry', 'tone' => 'danger', 'alt' => 'Blurry marriage contract upload example'],
                ['src' => 'images/document-guide/document-cropped.svg', 'label' => 'Cropped', 'tone' => 'danger', 'alt' => 'Cropped marriage contract upload example'],
                ['src' => 'images/document-guide/document-correct.svg', 'label' => 'Correct', 'tone' => 'success', 'alt' => 'Correct readable marriage contract upload example'],
            ]"
        />

        <x-upload-requirement-card
            title="Medical Record (if any)"
            description="Optional health record to help the school prepare support."
            name="medical_record"
            :uploaded="$applicant?->medical_record_url"
            accept="image/jpeg,image/jpg,image/png,application/pdf"
            :maxSizeMB="10"
            guide-title="Preparation note"
            :guide="[
                'Upload lab results, prescriptions, or health notes if relevant.',
                'This helps the school prepare support for the student.',
                'Make sure the clinic name, date, and details are readable.',
            ]"
            :guide-images="[
                ['src' => 'images/document-guide/document-blurry.svg', 'label' => 'Blurry', 'tone' => 'danger', 'alt' => 'Blurry medical record upload example'],
                ['src' => 'images/document-guide/document-cropped.svg', 'label' => 'Cropped', 'tone' => 'danger', 'alt' => 'Cropped medical record upload example'],
                ['src' => 'images/document-guide/document-correct.svg', 'label' => 'Correct', 'tone' => 'success', 'alt' => 'Correct readable medical record upload example'],
            ]"
        />

    </div>
</section>
