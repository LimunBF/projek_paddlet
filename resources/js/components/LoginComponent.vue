<template>
    <div v-if="!isLoggedIn" class="login-container">
    
        <div class="login-container">
            <h2>Login untuk Melanjutkan</h2>
            <form @submit.prevent="handleLogin">
                <div v-if="errorMessage" class="error-message">
                    {{ errorMessage }}
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" v-model="email" required />
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        v-model="password"
                        required
                    />
                </div>

                <button type="submit" :disabled="isLoading">
                    {{ isLoading ? "Loading..." : "Login" }}
                </button>
            </form>
        </div>
        <hr style="margin: 40px auto; max-width: 400px;">
    </div>
</template>

<script>
export default {
    data() {
        return {
            email: "",
            password: "",
            errorMessage: null,
            isLoading: false,
            isLoggedIn: false 
        };
    },
    mounted() {
        if (localStorage.getItem('api_token')) {
        this.isLoggedIn = true;
        }
    },
    methods: {
        async handleLogin() {
            this.isLoading = true;
            this.errorMessage = null;

            try {
                const response = await axios.post("/api/login", {
                    email: this.email,
                    password: this.password,
                });
                

                const token = response.data.token;
                console.log("Login berhasil, token diterima:", token);

                localStorage.setItem("api_token", token);
                this.isLoggedIn = true;
                alert("Login Berhasil!");
                window.location.reload(); 
            } catch (error) {
                console.error("Login gagal:", error);
                this.errorMessage =
                    "Email atau password salah. Silakan coba lagi.";
            } finally {
                this.isLoading = false;
            }
        },
    },
};
</script>

<style scoped>
.login-container {
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
    box-sizing: border-box; /* Mencegah padding merusak layout */
}
button {
    padding: 10px 15px;
    background-color: #007bff;
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