<div x-transition class="parent-info-flow">
    <section class="parent-section-card">
        <div class="form-divider">Father's Information</div>
        <div class="parent-name-grid">
            <x-form-input label="Last Name" name="father_last_name" :col="1" x-model="form.father_last_name" />
            <x-form-input label="First Name" name="father_first_name" :col="1" x-model="form.father_first_name" />
            <x-form-input label="Middle Name" name="father_middle_name" :col="1" x-model="form.father_middle_name" />
        </div>
        <div class="parent-occupation-field">
            <x-form-input label="Occupation" name="father_occupation" :col="1" x-model="form.father_occupation" />
        </div>
    </section>

    <section class="parent-section-card">
        <div class="form-divider">Mother's Information</div>
        <div class="parent-name-grid">
            <x-form-input label="Last Name" name="mother_last_name" :col="1" x-model="form.mother_last_name" />
            <x-form-input label="First Name" name="mother_first_name" :col="1" x-model="form.mother_first_name" />
            <x-form-input label="Middle Name" name="mother_middle_name" :col="1" x-model="form.mother_middle_name" />
        </div>
        <div class="parent-occupation-field">
            <x-form-input label="Occupation" name="mother_occupation" :col="1" x-model="form.mother_occupation" />
        </div>
    </section>

    <section class="parent-section-card">
        <div class="form-divider">Address</div>
        <label class="same-address-toggle">
            <input type="checkbox" x-model="form.same_as_permanent" class="same-address-checkbox">
            <span class="same-address-checkmark" :class="{ 'is-checked': form.same_as_permanent }" aria-hidden="true">
                <svg width="12" height="10" viewBox="0 0 12 10" fill="none">
                    <path d="M1 5.2L4.2 8.2L11 1.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <span>Same as Student Present Address</span>
        </label>
        <input type="hidden" name="home_address" :value="compiledHomeAddress">
        <div class="permanent-address-grid" x-show="!form.same_as_permanent" x-cloak>
            <div class="form-group address-country-field">
                <x-form-field-label optional>Country</x-form-field-label>
                <div class="country-combobox" x-data="{ open: false, search: '' }" @click.outside="open = false">
                    <button type="button" class="country-combobox-trigger" @click="open = !open">
                        <template x-if="selectedPermanentCountry?.flagPng">
                            <img :src="selectedPermanentCountry.flagPng" alt="" class="country-flag-img-static">
                        </template>
                        <span class="country-combobox-value" x-text="selectedPermanentCountry ? selectedPermanentCountry.name : 'Search country...'"></span>
                        <svg class="country-combobox-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-show="open" x-transition class="country-combobox-menu">
                        <input type="text" class="country-combobox-search" placeholder="Search country..." x-model="search" @keydown.escape="open = false">
                        <div class="country-combobox-list">
                            <template x-for="country in filteredCountries(search)" :key="'home-country-' + country.code">
                                <button type="button" class="country-combobox-option" @click="selectPermanentCountry(country); open = false; search = ''">
                                    <img :src="country.flagPng" alt="" class="country-option-flag">
                                    <span class="country-option-name" x-text="country.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group address-postal-field">
                <x-form-field-label optional>Postal Code</x-form-field-label>
                <input type="text" name="home_postal_code" class="plain-input" placeholder="Postal code" x-model="form.home_postal_code">
            </div>
            <div class="form-group address-full-field">
                <x-form-field-label optional>Complete Address</x-form-field-label>
                <input type="text" name="home_street_address" class="plain-input" placeholder="Permanent complete address" x-model="form.home_street_address">
            </div>
        </div>
    </section>

    <section class="parent-section-card">
        <div class="form-divider">Contact Information</div>
        <div class="parent-contact-grid">
            <div class="form-group parent-mobile-field">
                <x-form-field-label required>Parent Mobile</x-form-field-label>
                <div class="contact-number-grid">
                    <div class="country-combobox phone-code-combobox" x-data="{ open: false, search: '' }" @click.outside="open = false">
                        <button type="button" class="country-combobox-trigger phone-code-trigger" @click="open = !open">
                            <template x-if="selectedParentCodeCountry?.flagPng">
                                <img :src="selectedParentCodeCountry.flagPng" alt="" class="country-flag-img-static">
                            </template>
                            <span class="country-combobox-value" x-text="form.parent_country_code || 'Code'"></span>
                            <svg class="country-combobox-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-show="open" x-transition class="country-combobox-menu phone-code-menu">
                            <input type="text" class="country-combobox-search" placeholder="Search code or country..." x-model="search" @keydown.escape="open = false">
                            <div class="country-combobox-list">
                                <template x-for="country in filteredCallingCountries(search)" :key="'parent-code-' + country.code">
                                    <button type="button" class="country-combobox-option" @click="selectCallingCode(country, 'parent_country_code'); open = false; search = ''">
                                        <img :src="country.flagPng" alt="" class="country-option-flag">
                                        <span class="country-option-name" x-text="country.name"></span>
                                        <span class="country-option-code" x-text="country.callingCode"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="parent_country_code" :value="form.parent_country_code">
                    <input type="tel" name="parent_mobile" class="plain-input phone-number-input" :placeholder="getPhonePlaceholder(form.parent_country_code)" x-model="form.parent_mobile"
                        @input="form.parent_mobile = formatPhoneNumber($event.target.value, form.parent_country_code)">
                </div>
            </div>
            <div class="form-group parent-email-field">
                <x-form-field-label>Parent Email</x-form-field-label>
                <input type="email" name="parent_email" class="plain-input" placeholder="parent@email.com" x-model="form.parent_email">
            </div>
        </div>
    </section>
</div>
