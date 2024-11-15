<template>
  <div class="chat-container container mx-auto max-w-5xl flex h-screen p-2 sm:p-4 bg-gray-50 rounded-lg shadow-lg">
    <!-- Cột trái (Conversation + People) -->
    <div class="left-pane w-full sm:w-1/2 md:w-1/3 border-r border-gray-300 pr-4 overflow-y-auto">
      <Conversation />
      <People />
    </div>

    <!-- Cột phải (Message) -->
    <div class="right-pane w-full sm:w-1/2 md:w-2/3 pl-4 flex flex-col">
      <Message />
    </div>
  </div>
</template>

<script>
import Conversation from './Conversation.vue';
import People from './People.vue';
import Message from './Message.vue';

export default {
  components: {
    Conversation,
    People,
    Message,
  },
};
</script>

<style scoped>
.chat-container {
  display: flex;
  flex-direction: column;
  background-color: #f9fafb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

@media (min-width: 640px) {
  .chat-container {
    flex-direction: row;
  }
}

.left-pane {
  padding-right: 1rem;
  border-right: 1px solid #e5e7eb;
  overflow-y: auto;
}

.right-pane {
  padding-left: 1rem;
  display: flex;
  flex-direction: column;
}
</style>
