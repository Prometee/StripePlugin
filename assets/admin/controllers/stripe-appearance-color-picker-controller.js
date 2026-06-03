import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['picker', 'text'];

    connect() {
        this.#syncPicker();
    }

    pickerTargetConnected(element) {
        if (this.hasTextTarget && this.textTarget.value) {
            element.value = this.#expandHex(this.textTarget.value);
        }
    }

    syncFromPicker() {
        this.textTarget.value = this.pickerTarget.value;
    }

    syncFromText() {
        if (/^#[0-9a-fA-F]{6}$/.test(this.textTarget.value)) {
            this.pickerTarget.value = this.textTarget.value;
        }
    }

    clear() {
        this.textTarget.value = '';
        this.pickerTarget.value = '#000000';
    }

    #syncPicker() {
        if (this.textTarget.value) {
            this.pickerTarget.value = this.#expandHex(this.textTarget.value);
        }
    }

    #expandHex(hex) {
        if (/^#[0-9a-fA-F]{3}$/.test(hex)) {
            return '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
        }
        return hex;
    }
}
