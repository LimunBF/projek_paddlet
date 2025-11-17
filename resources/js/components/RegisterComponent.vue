<template>
  <div v-if="!isLoggedIn" class="register-container">
    <h2>Atau Daftar Akun Baru</h2>
    <form @submit.prevent="handleRegister">
      
      <div v-if="errorMessage" class="error-message">
        <div v-html="errorMessage"></div>
      </div>

      <div class="form-group">
        <label for="name">Nama</label>
        <input type="text" id="name" v-model="name" required>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" v-model="email" required>
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" v-model="password" required>
      </div>

      <div class="form-group">
        <label for="password_confirmation">Konfirmasi Password</label>
        <input type="password" id="password_confirmation" v-model="password_confirmation" required>
      </div>
      
      <button type="submit" :disabled="isLoading">
        {{ isLoading ? 'Mendaftar...' : 'Daftar' }}
      </button>

    </form>
  </div>
</template>

<script>
export default {
  data() {
    return {
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      errorMessage: null,
      isLoading: false,
      isLoggedIn: false // Sembunyikan form jika sudah login
    };
  },
  mounted() {
    // Cek apakah sudah ada token saat komponen dimuat
    if (localStorage.getItem('api_token')) {
      this.isLoggedIn = true;
    }
  },
  methods: {
    async handleRegister() {
      this.isLoading = true;
      this.errorMessage = null;

      // Cek apakah password cocok
      if (this.password !== this.password_confirmation) {
        this.errorMessage = 'Password dan Konfirmasi Password tidak cocok.';
        this.isLoading = false;
        return;
      }

      try {
        // 1. Panggil API register (menggunakan form-data)
        // Axios akan otomatis menggunakan 'application/json' jika kita kirim objek JS
        await axios.post('/api/register', {
          name: this.name,
          email: this.email,
          password: this.password,
          password_confirmation: this.password_confirmation
        });

        // 2. Jika register berhasil, langsung login
        const response = await axios.post('/api/login', {
          email: this.email,
          password: this.password
        });
        
        // 3. Ambil dan simpan token
        const token = response.data.token;
        localStorage.setItem('api_token', token);

        // 4. Beri tahu user dan refresh
        alert('Registrasi & Login Berhasil!');
        window.location.reload();

      } catch (error) {
        console.error('Registrasi gagal:', error);
        if (error.response && error.response.data.errors) {
          // Tangani error validasi dari Laravel
          const errors = error.response.data.errors;
          let errorHtml = '<ul>';
          for (const key in errors) {
            errorHtml += `<li>${errors[key][0]}</li>`;
          }
          errorHtml += '</ul>';
          this.errorMessage = errorHtml;
        } else {
          this.errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
        }
      } finally {
        this.isLoading = false;
      }
    }
  }
}
</script>

<style scoped>
/* Anda bisa copy-paste style dari LoginComponent jika mau, atau gunakan ini */
.register-container {
  max-width: 400px;
  margin: 40px auto;
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #f9f9f9;
}
.form-group {
  margin-bottom: 15px;
}
label {
  display: block;
  margin-bottom: 5px;
}
input {
  width: 100%;
  padding: 8px;
  box-sizing: border-box;
}
button {
  padding: 10px 15px;
  background-color: #17a2b8; /* Warna biru-hijau */
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
button:disabled {
  background-color: #ccc;
}
.error-message {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 15px;
}
</style>