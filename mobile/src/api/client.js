const BASE_URL = 'http://192.168.1.44:8001/api';

export const apiClient = {
  async getChatConversations(token) {
    try {
      const response = await fetch(`${BASE_URL}/chat/conversations`, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Gagal memuat percakapan');
      return data;
    } catch (error) {
      console.error('Get Chat Conversations Error:', error);
      throw error;
    }
  },

  async testConnection() {
    try {
      const response = await fetch(`${BASE_URL}/test`);
      const data = await response.json();
      return data;
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  },

  async login(email, password) {
    try {
      const response = await fetch(`${BASE_URL}/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          email,
          password,
          device_name: 'mobile_app',
        }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Gagal login');
      }

      return data;
    } catch (error) {
      console.error('Login Error:', error);
      throw error;
    }
  },

  async register(
    name, email, password, password_confirmation, 
    role, phone, address, customer_type, ppui_number, 
    nik, birth_date, passport_number,
    languages = [], educations = [], workExperiences = [], 
    referenceText = '', photo = null, ktp = null, supportingDocuments = []
  ) {
    try {
      const formData = new FormData();
      formData.append('name', name);
      formData.append('email', email);
      formData.append('password', password);
      formData.append('password_confirmation', password_confirmation);
      formData.append('role', role);
      formData.append('phone', phone);
      formData.append('address', address);
      formData.append('device_name', 'mobile_app');

      if (role === 'customer') {
        formData.append('customer_type', customer_type);
        if (customer_type === 'company') {
          formData.append('ppui_number', ppui_number);
        }
      }

      if (role === 'muthowif') {
        formData.append('nik', nik);
        formData.append('birth_date', birth_date);
        formData.append('passport_number', passport_number);
        formData.append('reference_text', referenceText);

        // Professional Lists
        languages.forEach((item, index) => {
          if (item) formData.append(`languages[${index}]`, item);
        });
        educations.forEach((item, index) => {
          if (item) formData.append(`educations[${index}]`, item);
        });
        workExperiences.forEach((item, index) => {
          if (item) formData.append(`work_experiences[${index}]`, item);
        });

        // Files
        if (photo) {
          formData.append('photo', {
            uri: photo,
            name: 'photo.jpg',
            type: 'image/jpeg',
          });
        }
        if (ktp) {
          formData.append('ktp_image', {
            uri: ktp,
            name: 'ktp.jpg',
            type: 'image/jpeg',
          });
        }
        supportingDocuments.forEach((uri, index) => {
          if (uri) {
            formData.append(`supporting_documents[${index}]`, {
              uri: uri,
              name: `doc_${index}.jpg`,
              type: 'image/jpeg',
            });
          }
        });
      }

      const response = await fetch(`${BASE_URL}/register`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'multipart/form-data',
        },
        body: formData,
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Gagal registrasi');
      }

      return data;
    } catch (error) {
      console.error('Register Error:', error);
      throw error;
    }
  },

  async sendOtp(phone, role) {
    try {
      const response = await fetch(`${BASE_URL}/otp/send`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ phone, role }),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Send OTP Error:', error);
      throw error;
    }
  },

  async getDashboardData(token) {
    try {
      const response = await fetch(`${BASE_URL}/customer/dashboard`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Dashboard Error:', error);
      throw error;
    }
  },

  async getMuthowifDashboardData(token) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/dashboard`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Muthowif Dashboard Error:', error);
      throw error;
    }
  },

  async getWalletData(token) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/wallet`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Get Wallet Data Error:', error);
      throw error;
    }
  },

  async requestWithdrawal(token, payload) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/withdrawals`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Gagal mengajukan withdraw');
      return data;
    } catch (error) {
      console.error('Request Withdrawal Error:', error);
      throw error;
    }
  },

  async getMuthowifServices(token) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/services`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Muthowif Services Error:', error);
      throw error;
    }
  },

  async updateMuthowifService(token, id, payload) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/services/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Update Service Error:', error);
      throw error;
    }
  },

  async getBlockedDates(token) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/blocked-dates`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Get Blocked Dates Error:', error);
      throw error;
    }
  },

  async addBlockedDate(token, payload) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/blocked-dates`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Add Blocked Date Error:', error);
      throw error;
    }
  },

  async deleteBlockedDate(token, id) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/blocked-dates/${id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Delete Blocked Date Error:', error);
      throw error;
    }
  },

  async getMuthowifBookings(token) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/bookings`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Get Bookings Error:', error);
      throw error;
    }
  },

  async getBookingDetail(token, id) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/bookings/${id}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Get Booking Detail Error:', error);
      throw error;
    }
  },

  async confirmBooking(token, id) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/bookings/${id}/confirm`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Confirm Booking Error:', error);
      throw error;
    }
  },

  async cancelBooking(token, id) {
    try {
      const response = await fetch(`${BASE_URL}/muthowif/bookings/${id}/cancel`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Cancel Booking Error:', error);
      throw error;
    }
  },

  async getProfile(token) {
    try {
      const response = await fetch(`${BASE_URL}/profile`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Get Profile Error:', error);
      throw error;
    }
  },

  async updateProfile(token, payload) {
    try {
      const response = await fetch(`${BASE_URL}/profile`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Update Profile Error:', error);
      throw error;
    }
  },

  async updatePublicProfile(token, payload) {
    try {
      const response = await fetch(`${BASE_URL}/profile/public`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Update Public Profile Error:', error);
      throw error;
    }
  },

  async updatePassword(token, payload) {
    try {
      const response = await fetch(`${BASE_URL}/profile/password`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Update Password Error:', error);
      throw error;
    }
  },

  async uploadProfilePhoto(token, imageUri) {
    try {
      const formData = new FormData();
      formData.append('photo', {
        uri: imageUri,
        name: 'photo.jpg',
        type: 'image/jpeg',
      });

      const response = await fetch(`${BASE_URL}/profile/photo`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'multipart/form-data',
        },
        body: formData,
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Upload Photo Error:', error);
      throw error;
    }
  },

  async uploadKtp(token, imageUri) {
    try {
      const formData = new FormData();
      formData.append('ktp', {
        uri: imageUri,
        name: 'ktp.jpg',
        type: 'image/jpeg',
      });

      const response = await fetch(`${BASE_URL}/profile/ktp`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'multipart/form-data',
        },
        body: formData,
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Upload KTP Error:', error);
      throw error;
    }
  },

  async uploadSupportingDocument(token, imageUri) {
    try {
      const formData = new FormData();
      formData.append('document', {
        uri: imageUri,
        name: 'document.jpg',
        type: 'image/jpeg',
      });

      const response = await fetch(`${BASE_URL}/profile/documents`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'multipart/form-data',
        },
        body: formData,
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Upload Document Error:', error);
      throw error;
    }
  },

  async deleteSupportingDocument(token, id) {
    try {
      const response = await fetch(`${BASE_URL}/profile/documents/${id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Delete Document Error:', error);
      throw error;
    }
  },

  async verifyOtp(phone, otp) {
    try {
      const response = await fetch(`${BASE_URL}/otp/verify`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ phone, otp }),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message);
      return data;
    } catch (error) {
      console.error('Verify OTP Error:', error);
      throw error;
    }
  },

  async getChatMessages(token, bookingId, afterId = null) {
    try {
      const params = afterId ? `?after_id=${afterId}` : '';
      const response = await fetch(`${BASE_URL}/bookings/${bookingId}/chat${params}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Gagal memuat pesan');
      return data;
    } catch (error) {
      console.error('Get Chat Messages Error:', error);
      throw error;
    }
  },

  async sendChatMessage(token, bookingId, body, imageUri = null) {
    try {
      const formData = new FormData();
      if (body && body.trim()) formData.append('body', body.trim());
      if (imageUri) {
        const filename = imageUri.split('/').pop();
        const ext = filename.split('.').pop().toLowerCase();
        const mimeMap = { jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', gif: 'image/gif', webp: 'image/webp' };
        formData.append('image', {
          uri: imageUri,
          name: filename,
          type: mimeMap[ext] || 'image/jpeg',
        });
      }

      const response = await fetch(`${BASE_URL}/bookings/${bookingId}/chat`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: formData,
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Gagal mengirim pesan');
      return data;
    } catch (error) {
      console.error('Send Chat Message Error:', error);
      throw error;
    }
  }
};
