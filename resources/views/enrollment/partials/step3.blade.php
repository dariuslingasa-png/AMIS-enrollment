<div x-transition class="address-contact-grid">
    <div class="form-divider">Present Address</div>
    <input type="hidden" name="address" :value="compiledPresentAddress">

    <div class="form-group address-country-field">
        <x-form-field-label required>Country</x-form-field-label>
        <div class="country-combobox" x-data="{ open: false, search: '' }" @click.outside="open = false">
            <button type="button" class="country-combobox-trigger" @click="open = !open">
                <template x-if="selectedCountry?.flagPng">
                    <img :src="selectedCountry.flagPng" alt="" class="country-flag-img-static">
                </template>
                <span class="country-combobox-value" x-text="selectedCountry ? selectedCountry.name : (countriesLoading ? 'Loading countries...' : 'Search country...')"></span>
                <svg class="country-combobox-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m6 9 6 6 6-6"/></svg>
            </button>
            <div x-show="open" x-transition class="country-combobox-menu">
                <input type="text" class="country-combobox-search" placeholder="Search country..." x-model="search" @keydown.escape="open = false">
                <div class="country-combobox-list">
                    <template x-for="country in filteredCountries(search)" :key="country.code">
                        <button type="button" class="country-combobox-option" @click="selectCountry(country); open = false; search = ''">
                            <img :src="country.flagPng" alt="" class="country-option-flag">
                            <span class="country-option-name" x-text="country.name"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
        <input type="hidden" name="country" :value="form.country">
    </div>
    <div class="form-group address-postal-field">
        <x-form-field-label optional>Postal Code</x-form-field-label>
        <input type="text" name="postal_code" class="plain-input" placeholder="Postal code" x-model="form.postal_code">
    </div>
    <div class="form-group address-full-field">
        <x-form-field-label required>Complete Address</x-form-field-label>
        <input type="text" name="street_address" class="plain-input" placeholder="House no., street, building, unit, city/province" x-model="form.street_address">
    </div>

    <div class="form-divider address-full-field">Contact Information</div>

    <div class="form-group address-full-field">
        <x-form-field-label required>Mobile Number</x-form-field-label>
        <div class="contact-number-grid">
            <div class="country-combobox phone-code-combobox" x-data="{ open: false, search: '' }" @click.outside="open = false">
                <button type="button" class="country-combobox-trigger phone-code-trigger" @click="open = !open">
                    <template x-if="selectedMobileCodeCountry?.flagPng">
                        <img :src="selectedMobileCodeCountry.flagPng" alt="" class="country-flag-img-static">
                    </template>
                    <span class="country-combobox-value" x-text="form.mobile_country_code || '+63'"></span>
                    <svg class="country-combobox-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div x-show="open" x-transition class="country-combobox-menu phone-code-menu">
                    <input type="text" class="country-combobox-search" placeholder="Search code or country..." x-model="search" @keydown.escape="open = false">
                    <div class="country-combobox-list">
                        <template x-for="country in filteredCallingCountries(search)" :key="'student-code-' + country.code">
                            <button type="button" class="country-combobox-option" @click="selectCallingCode(country, 'mobile_country_code'); open = false; search = ''">
                                <img :src="country.flagPng" alt="" class="country-option-flag">
                                <span class="country-option-name" x-text="country.name"></span>
                                <span class="country-option-code" x-text="country.callingCode"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <input type="hidden" name="mobile_country_code" :value="form.mobile_country_code">
            <input type="tel" name="mobile_number" class="plain-input phone-number-input" :placeholder="getPhonePlaceholder(form.mobile_country_code)" x-model="form.mobile_number"
                @input="form.mobile_number = formatPhoneNumber($event.target.value, form.mobile_country_code)">
        </div>
        <span class="field-hint">Country code is based on country of residence, but you can change it.</span>
    </div>
</div>
