<template>
  <div class="otp-container">
    <p>Please enter the OTP sent to your email.</p>
    <form @submit.prevent="verifyOTP" class="otp-form">
      <div class="form-group">
        <label class="otp-label">OTP Code</label>
        <input type="text" v-model="otp" required />
      </div>
      <button type="submit" :disabled="isExpired" class="verify-button">
        Verify OTP
      </button>
      <p v-if="countdown !== null" class="countdown">Time left: {{ formattedTime }}</p>
      <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>
      <p v-if="successfullMessage" class="successful-message">{{ successfullMessage }}</p>
    </form>
    <p @click="resendOTP" class="resend-link">Resend OTP</p>
  </div>
</template>

<script>
import moment from 'moment';

export default {
  inject: ['$axios'],
  data() {
    return {
      otp: '',
      errorMessage: '',
      successfullMessage: '',
      countdown: null,
      isExpired: false,
      expiredAt: null,
      countdownInterval: null, // Biến để lưu interval
    };
  },
  watch:{
     errorMessage(newValue) {
         if(newValue !== ''){
             this.successfullMessage = '';
         }
     },
     successfullMessage(newValue) {
         if(newValue !== ''){
             this.errorMessage = '';
         }
     }
  },
  computed: {
    formattedTime() {
      if (this.countdown <= 0) {
        return "00:00";
      }
      const minutes = Math.floor(this.countdown / 60);
      const seconds = Math.floor(this.countdown % 60);
      return `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
    },
  },
  mounted() {
    const email = this.$route.query.email;
    const userId = this.$route.query.user_id;
    if (email && userId) {
      const storedExpiredAt = localStorage.getItem('otpExpiredAt');
      if (storedExpiredAt && moment(storedExpiredAt).isAfter(moment())) {
        this.expiredAt = moment(storedExpiredAt);
        this.startCountdown();
      } else {
        this.sendOTP(email,userId);
      }
    } else {
      this.errorMessage = "URL không hợp lệ";
    }
  },
  beforeDestroy() {
    // Xóa interval khi component bị hủy để tránh chạy tiếp tục
    clearInterval(this.countdownInterval);
  },
  methods: {
    async sendOTP(email,userId) {
      try {
        const response = await this.$axios.post('/api/send-otp', { email ,user_id : userId });
        this.expiredAt = moment(response.data.expired_at);

        // Lưu expiredAt vào localStorage
        localStorage.setItem('otpExpiredAt', this.expiredAt.toISOString());

        this.startCountdown();
        return response;
      } catch (error) {
        this.errorMessage = error.response.data.message;
      }
    },
    startCountdown() {
      // Xóa interval cũ nếu có
      if (this.countdownInterval) {
        clearInterval(this.countdownInterval);
      }

      this.countdown = moment.duration(this.expiredAt.diff(moment())).asSeconds();
      this.isExpired = false;

      // Tạo interval mới
      this.countdownInterval = setInterval(() => {
        if (this.countdown > 0) {
          this.countdown--;
        } else {
          this.isExpired = true;
          clearInterval(this.countdownInterval);
          localStorage.removeItem('otpExpiredAt'); // Xóa expiredAt khi hết hạn
        }
      }, 1000);
    },
    async verifyOTP() {
      try {
       const verify = await this.$axios.post('/api/verify-otp', {
           email: this.$route.query.email,
           otp: this.otp ,
           user_id : this.$route.query.user_id
         });
        localStorage.removeItem('otpExpiredAt'); // Xóa expiredAt khi xác thực thành công
        clearInterval(this.countdownInterval); // Xóa interval khi xác thực thành công
        this.successfullMessage = verify.data.message;

        setTimeout(() => {
          this.$router.push('/login');
        }, 2000);
      } catch (error) {
        this.errorMessage = error.response.data.message;
      }
    },
    async resendOTP() {
      this.errorMessage = '';
      if (
        this.$route.query.email
        &&
        this.$route.query.user_id) {
        localStorage.removeItem('otpExpiredAt'); // Xóa expiredAt cũ
        const resend = await this.sendOTP(
          this.$route.query.email,
          this.$route.query.user_id
        ); // Gửi lại OTP và khởi tạo countdown mới
         this.successfullMessage = resend.data.message;
      }
    },
  },
};
</script>

<style scoped>
.otp-container {
  max-width: 400px;
  margin: 0 auto;
  padding: 20px;
  background-color: #f5f5f5;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

h2 {
  color: #333;
  text-align: center;
  margin-bottom: 10px;
}

p {
  text-align: center;
}

.otp-form {
  display: flex;
  flex-direction: column;
}

.form-group {
  margin-bottom: 15px;
  text-align: center; /* Center-align the label and input */
}

.otp-label {
  font-weight: bold;
  display: block;
  margin-bottom: 5px;
}

input[type="text"] {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
  box-sizing: border-box;
}

.verify-button {
  background-color: #3498db;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
  font-weight: bold;
  margin-top: 10px;
  transition: background-color 0.3s;
}

.verify-button:hover {
  background-color: #2980b9;
}

.countdown {
  font-size: 14px;
  color: #555;
  text-align: center;
  margin-top: 15px;
}

.error-message {
  color: #e74c3c;
  font-size: 14px;
  text-align: center;
  margin-top: 15px;
}

.successful-message {
  color: #27ae60;
  font-size: 14px;
  text-align: center;
  margin-top: 15px;
}

.resend-link {
  color: #27ae60; /* Dark green color */
  font-size: 14px;
  cursor: pointer;
  text-align: center;
  margin-top: 15px;
  font-weight: bold;
}
</style>