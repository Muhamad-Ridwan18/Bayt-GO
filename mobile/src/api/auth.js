import { API_BASE_URL } from '../config/api';

const DEVICE_NAME = 'mobile_app';

async function parseResponse(response) {
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    let message = data.message;
    if (!message && data.errors) {
      message = Object.values(data.errors).flat().join('\n');
    }
    throw new Error(message || 'Permintaan gagal');
  }
  return data;
}

export async function login(email, password) {
  const response = await fetch(`${API_BASE_URL}/login`, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password, device_name: DEVICE_NAME }),
  });
  return parseResponse(response);
}

export async function sendOtp(phone, role) {
  const response = await fetch(`${API_BASE_URL}/otp/send`, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ phone, role }),
  });
  return parseResponse(response);
}

export async function verifyOtp(phone, otp) {
  const response = await fetch(`${API_BASE_URL}/otp/verify`, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ phone, otp }),
  });
  return parseResponse(response);
}

export async function registerCustomer(payload) {
  const formData = new FormData();
  formData.append('name', payload.name);
  formData.append('email', payload.email);
  formData.append('password', payload.password);
  formData.append('password_confirmation', payload.passwordConfirmation);
  formData.append('role', 'customer');
  formData.append('phone', payload.phone);
  formData.append('address', payload.address);
  if (payload.country) {
    formData.append('country', payload.country);
  }
  formData.append('customer_type', payload.customerType);
  formData.append('device_name', DEVICE_NAME);
  if (payload.customerType === 'company' && payload.ppuiNumber) {
    formData.append('ppui_number', payload.ppuiNumber);
  }

  const response = await fetch(`${API_BASE_URL}/register`, {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body: formData,
  });
  return parseResponse(response);
}

export async function registerMuthowif(payload) {
  const formData = new FormData();
  formData.append('name', payload.name);
  formData.append('email', payload.email);
  formData.append('password', payload.password);
  formData.append('password_confirmation', payload.passwordConfirmation);
  formData.append('role', 'muthowif');
  formData.append('phone', payload.phone);
  formData.append('address', payload.address);
  if (payload.country) {
    formData.append('country', payload.country);
  }
  formData.append('nik', payload.nik);
  formData.append('birth_date', payload.birthDate);
  formData.append('passport_number', payload.passportNumber);
  formData.append('reference_text', payload.referenceText || '');
  if (payload.referralCode?.trim()) {
    formData.append('muthowif_referral_code', payload.referralCode.trim().toUpperCase());
  }
  formData.append('device_name', DEVICE_NAME);

  payload.languages.forEach((lang, i) => {
    if (lang) formData.append(`languages[${i}]`, lang);
  });
  payload.educations.forEach((edu, i) => {
    if (edu) formData.append(`educations[${i}]`, edu);
  });
  payload.workExperiences.forEach((exp, i) => {
    if (exp) formData.append(`work_experiences[${i}]`, exp);
  });

  formData.append('photo', {
    uri: payload.photo.uri,
    name: 'photo.jpg',
    type: 'image/jpeg',
  });
  formData.append('ktp_image', {
    uri: payload.ktp.uri,
    name: 'ktp.jpg',
    type: 'image/jpeg',
  });

  (payload.supportingDocuments || []).forEach((doc, i) => {
    formData.append(`supporting_documents[${i}]`, {
      uri: doc.uri,
      name: doc.name || `document-${i}.pdf`,
      type: doc.mimeType || doc.type || 'application/pdf',
    });
  });

  const response = await fetch(`${API_BASE_URL}/register`, {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body: formData,
  });
  return parseResponse(response);
}

export async function logout(token) {
  const response = await fetch(`${API_BASE_URL}/logout`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });
  return parseResponse(response);
}

export async function sendPasswordResetOtp(phone) {
  const response = await fetch(`${API_BASE_URL}/password/forgot`, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({ phone }),
  });
  return parseResponse(response);
}

export async function resetPassword({ token, otp, password, passwordConfirmation }) {
  const response = await fetch(`${API_BASE_URL}/password/reset`, {
    method: 'POST',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: JSON.stringify({
      token,
      otp,
      password,
      password_confirmation: passwordConfirmation,
    }),
  });
  return parseResponse(response);
}
