<template>
  <div class="chat-container container mx-auto max-w-5xl flex flex-col sm:flex-row p-2 sm:p-4 bg-gray-50 rounded-lg shadow-lg">
    <!-- Hiển thị Conversation và People trên màn hình nhỏ, ẩn khi đang xem Message -->
    <div v-if="!isChatOpen || isDesktop" class="left-pane w-full sm:w-1/2 md:w-1/3 border-r border-gray-300 pr-4 overflow-y-auto">
      <profile/>
      <Conversation :dataMoveConvToTop="dataMoveConvToTop" @open-chat="openChat" />
      <People @move-conv-to-top="moveConvToTop" @open-chat="openChat" />
    </div>

    <!-- Hiển thị Message component khi đang xem chat hoặc trên màn hình lớn -->
    <div v-if="isChatOpen" class="right-pane w-full sm:w-1/2 md:w-2/3 pl-4 flex flex-col">
      <button v-if="!isDesktop" @click="closeChat" class="p-2 text-blue-500 font-semibold flex items-center mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707a1 1 0 00-1.414-1.414L9 11.586 7.707 10.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        Quay lại
      </button>
      <Message @move-conv-to-top="moveConvToTop" :dataMessage="dataMessage" />
    </div>
  </div>
</template>

<script>
import Conversation from './Conversation.vue';
import People from './People.vue';
import Message from './Message.vue';
import Profile from '../Auth/Profile.vue';

export default {
  components: {
    Conversation,
    People,
    Message,
    Profile
  },
  data() {
    return {
      dataMoveConvToTop: {},
      isChatOpen: false,
      isDesktop: window.innerWidth >= 640,
      dataMessage : {}
    };
  },
  methods: {
    moveConvToTop(data)
    {
       this.dataMoveConvToTop = data;
    },
    openChat(data,type) {
      this.isChatOpen = true;
      this.dataMessage = {
        id : data,type
      };
    },
    closeChat() {
      this.isChatOpen = false;
    },
    handleResize() {
      this.isDesktop = window.innerWidth >= 640;
      if (this.isDesktop) {
        this.isChatOpen = false;
      }
    },
  },
  mounted() {
    window.addEventListener('resize', this.handleResize);
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.handleResize);
  },
};
</script>

<style scoped>
/* Container styles */
.chat-container {
  display: flex;
  flex: 1;
  background-color: #f9fafb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Left pane styles */
.left-pane {
  padding-right: 1rem;
  border-right: 1px solid #e5e7eb;
  overflow-y: auto; /* Ensure the scrollbar is visible */
}

/* Right pane styles */
.right-pane {
  padding-left: 1rem;
  display: flex;
  flex-direction: column;
  overflow-y: auto; /* Ensure the scrollbar is visible */
}

/* Customize scrollbar for webkit browsers */
.left-pane::-webkit-scrollbar,
.right-pane::-webkit-scrollbar {
  width: 8px; /* Adjust the width of the scrollbar */
}

.left-pane::-webkit-scrollbar-track,
.right-pane::-webkit-scrollbar-track {
  background: #e5e7eb; /* Light gray for the track */
  border-radius: 10px; /* Rounded corners */
}

.left-pane::-webkit-scrollbar-thumb,
.right-pane::-webkit-scrollbar-thumb {
  background: #c7cedd; /* Blue color (similar to icon color) */
  border-radius: 10px; /* Rounded corners */
  border: 2px solid transparent; /* Optional padding effect */
  background-clip: padding-box;
}

/* Hover effect for scrollbar */
.left-pane::-webkit-scrollbar-thumb:hover,
.right-pane::-webkit-scrollbar-thumb:hover {
  background: #c7cedd; /* Darker blue on hover */
}
</style>
