import { Application } from '@hotwired/stimulus';
import ColorPickerController from './controllers/stripe-appearance-color-picker-controller.js';
import './form.scss';
import './stripe.scss';

const app = Application.start();
app.register('stripe-appearance-color-picker', ColorPickerController);
