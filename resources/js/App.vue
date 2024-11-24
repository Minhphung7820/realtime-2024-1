<template>
    <div class="app-container">
        <!-- Thanh điều hướng -->
        <!-- <nav class="header">
            <router-link to="/">Home</router-link>
            <router-link v-if="isAuthenticated" to="/get-profile">Get Profile</router-link>
            <router-link v-if="isAuthenticated" to="/chat">Chat</router-link>
            <router-link v-else to="/login">Login</router-link>
        </nav> -->
<!-- Nội dung trang -->
   <div v-if="showPopup" class="modal-overlay">
      <div class="modal">
        <h2>Tạo mã PIN</h2>
        <em>(Tạo mã PIN để bảo vệ và đồng bộ hóa tin nhắn)</em>
        <!-- Ô nhập mã PIN -->
        <div class="pin-inputs">
          <input
            v-for="(digit, index) in pin"
            :key="index"
            type="password"
            maxlength="1"
            v-model="pin[index]"
            @input="focusNext(index)"
            @keydown.backspace="focusPrevious($event, index)"
            :ref="'pin' + index"
          />
        </div>
        <button @click="submitPin">Xác nhận</button>

        <!-- Hiển thị mã recovery -->
        <div v-if="isFirst" class="recovery-section">
          <h3>Mã khôi phục</h3>
          <p class="recovery-code">{{ recoveryKey }}</p> <br>
          <button @click="copyRecoveryKey">Copy mã khôi phục</button>
        </div>

        <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>
      </div>
    </div>
        <!-- Đây là nơi Vue Router sẽ hiển thị các component theo route -->
        <router-view class="content"></router-view>
    </div>
</template>

<script>
import { onlineStore } from "./stores/UserOnline.js"; // Đường dẫn tới store của bạn
import {
    generateDeviceId,
    generateKeyPair,
    encryptMessageWithPublicKey,
    decryptMessageWithPrivateKey,
    importPublicKey,
    importPrivateKey,
    encryptPrivateKeyWithPin,
    decryptPrivateKeyWithPin,
    exportPrivateKey,
    generateRecoveryKey,
    encryptPrivateKeyWithRecoveryKey,
    decryptPrivateKeyWithRecoveryKey,
    generateMasterKey,
    encryptPrivateKeyWithMasterKey,
    decryptPrivateKeyWithMasterKey,
    encryptMasterKeyWithPin,
    decryptMasterKeyWithPin,
    encryptMasterKeyWithRecovery,
    decryptMasterKeyWithRecovery,
    checkKeyPair
    } from "./utils/functions.js"
export default {
    inject: ['$axios','$userProfile','$socket'],
    name: 'App',
    data() {
        return {
            pin: ['', '', '', '', '', ''], // Mảng lưu các số của mã PIN
            errorMessage: '', // Hiển thị lỗi nếu mã PIN không hợp lệ
            recoveryKey: "Nhập mã pin để lấy mã Recovery", // Ví dụ mã recovery, thay bằng giá trị thực tế
            showPopup: false,
            isAuthenticated: false,
            isFirst : false
        };
    },
    async created() {
        // Kiểm tra token khi component được khởi tạo
        this.checkAuthentication();
        if(this.isAuthenticated){
        // Gọi store để fetch dữ liệu
        const store = onlineStore();
        await store.fetchData(); // Gọi action fetchData từ store
        store.initializeSocket(this.$socket)
        //
        await this.init();
        }
    },
    watch: {
     '$route'() {
         this.checkAuthentication();
     }
    },
    methods: {
     async setKey(pin)
       {
         const {publicKey, privateKey} = await generateKeyPair();
         const masterkey = generateMasterKey();
         const recoverykey = generateRecoveryKey();
         const privatekeyDecryptedByMasterKey = encryptPrivateKeyWithMasterKey(privateKey,masterkey);
         const masterkeyDecryptedByRecoverycode = encryptMasterKeyWithRecovery(masterkey,recoverykey);
         const masterkeyDecodeByPin = encryptMasterKeyWithPin(masterkey,pin);
         return {
            privateKey,
            publicKey,
            privatekeyDecryptedByMasterKey,
            masterkeyDecryptedByRecoverycode,
            recoverykey,
            masterkeyDecodeByPin
         }
    },
    async init(){
        try {
            const response = await this.$axios.post(`/api/init`,{
                device_id: generateDeviceId()
            });
            if(response.data.is_first){
               this.showPopup = true;
               this.isFirst = true;
            }else{
               const privateKey = localStorage.getItem('privateKey');
               if(!privateKey){
                 this.showPopup = true;
                 this.isFirst = false;
               }else{
                 const keyPair = await checkKeyPair(privateKey, this.$userProfile.public_key);
                 if(!keyPair){
                    this.showPopup = true;
                    this.isFirst = false;
                 }else{
                    this.showPopup = false;
                    this.isFirst = false;
                 }
               }
            }
        } catch (error) {

        }
    },
    focusNext(index) {
      if (this.pin[index] !== '' && index < 5) {
        this.$refs[`pin${index + 1}`][0].focus(); // Focus vào ô tiếp theo
      }
    },
    focusPrevious(event, index) {
      if (event.key === 'Backspace' && this.pin[index] === '' && index > 0) {
        this.$refs[`pin${index - 1}`][0].focus(); // Focus vào ô trước
      }
    },
    async submitPin() {
      const pinCode = Array.isArray(this.pin) ? this.pin.join('') : String(this.pin);
      if (pinCode.length !== 6) {
        this.errorMessage = 'Vui lòng nhập đủ 6 số.';
        return;
      }
      try {
         if(this.isFirst){
            const setKey = await this.setKey(pinCode);
            const publicKey = setKey.publicKey;
            const privatekeyDecryptedByMasterKey = setKey.privatekeyDecryptedByMasterKey;
            const masterkeyDecryptedByRecoverycode = setKey.masterkeyDecryptedByRecoverycode;
            const masterkeyDecodeByPin = setKey.masterkeyDecodeByPin;
            const recoverykey = setKey.recoverykey;
            const privateKey= setKey.privateKey;
            await this.$axios.post(`/api/add-key`, {
                 publicKey,
                 privatekeyDecryptedByMasterKey,
                 masterkeyDecryptedByRecoverycode,
                 masterkeyDecodeByPin
            });
            this.recoveryKey = recoverykey;
            localStorage.setItem('privateKey',privateKey);
            window.location.reload();
         }else{
             const privateKey= localStorage.getItem('privateKey');
             const keyPair = await checkKeyPair(privateKey, this.$userProfile.public_key);
             if(!privateKey || !keyPair){
                 const masterkeyEncryptedByPin = this.$userProfile.encrypted_master_key_with_pin;
                 const masterkeyDecryptedByPin = decryptMasterKeyWithPin(masterkeyEncryptedByPin,pinCode);
                 if(!masterkeyDecryptedByPin){
                    alert("Mã pin không đúng !");
                    return;
                 }
                 const privateKeyDecrypt = decryptPrivateKeyWithMasterKey(this.$userProfile.encrypted_private_key,masterkeyDecryptedByPin);
                 localStorage.setItem('privateKey',privateKeyDecrypt);
                 window.location.reload();
             }
         }
      } catch (error) {
        console.log(error);
      }
    },
    copyRecoveryKey() {
       navigator.clipboard.writeText(this.recoveryKey).then(
            () => {
              alert('Đã copy mã khôi phục!');
            },
            () => {
              alert('Không thể copy mã khôi phục. Vui lòng thử lại.');
            }
       );
    },
    checkAuthentication() {
            // Kiểm tra xem token có tồn tại trong localStorage không
            this.isAuthenticated = !!localStorage.getItem('token');
        }
    }
};
</script>

<style scoped>
/* Đặt cấu trúc flex cho App.vue */
.app-container {
    display: flex;
    flex-direction: column;
    height: 100vh; /* Chiếm toàn bộ chiều cao màn hình */
}

.header {
    padding: 10px;
    background-color: #333;
    color: white;
}

.header a {
    color: white;
    margin-right: 10px;
    text-decoration: none;
}

.header a.router-link-active {
    font-weight: bold;
}

.content {
    flex: 1; /* Chiếm không gian còn lại */
    overflow: hidden; /* Loại bỏ cuộn */
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
.modal {
  background: white;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
  text-align: center;
  width: 500px; /* Tăng chiều rộng */
  max-width: 90%;
}

.pin-inputs {
  display: flex;
  justify-content: space-between;
  margin: 30px 0; /* Tăng khoảng cách */
}

.pin-inputs input {
  width: 50px; /* Tăng chiều rộng của ô nhập mã PIN */
  height: 60px; /* Tăng chiều cao của ô nhập mã PIN */
  text-align: center;
  font-size: 24px; /* Tăng kích thước chữ */
  border: 2px solid #ccc; /* Tăng độ dày viền */
  border-radius: 5px;
  box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.1);
}

.recovery-section {
  margin-top: 30px; /* Tăng khoảng cách phía trên */
}

.recovery-code {
  font-family: monospace;
  font-size: 18px; /* Tăng kích thước chữ */
  background-color: #f9f9f9; /* Nền sáng hơn */
  padding: 15px; /* Tăng khoảng cách padding */
  border-radius: 8px; /* Viền bo tròn hơn */
  display: inline-block;
  word-break: break-word; /* Tự động xuống dòng khi mã dài */
  text-align: left;
}

button {
  background-color: #3498db;
  color: white;
  border: none;
  padding: 12px 25px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 18px; /* Tăng kích thước chữ */
  margin-top: 20px;
  transition: background-color 0.3s;
}

button:hover {
  background-color: #2980b9;
}

.error-message {
  color: #e74c3c;
  margin-top: 10px;
  font-size: 14px;
}

</style>
