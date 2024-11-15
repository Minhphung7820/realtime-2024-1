<template>
  <div class="chat-container container mx-auto max-w-5xl flex h-screen p-4">
    <!-- Cột trái (Conversation + People) -->
    <div class="w-1/4 border-r border-gray-300">
      <Conversation />
      <People />
    </div>

    <!-- Cột phải (Message) -->
    <div class="w-3/4">
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
  flex-direction: row;
}
</style>
