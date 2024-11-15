<template>
  <div class="conversation-list bg-gray-100 p-4 border-b">
    <h3 class="font-bold text-lg mb-2">Cuộc trò chuyện</h3>
    <ul>
      <li
        v-for="(conversation, index) in conversations"
        :key="index"
        class="p-2 border-b cursor-pointer hover:bg-gray-200"
      >
        {{ conversation.name }}
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  data() {
    return {
      conversations: [
        { name: 'Trò chuyện với A' },
        { name: 'Trò chuyện với B' },
        { name: 'Trò chuyện với C' },
      ],
    };
  },
};
</script>

<style scoped>
.conversation-list ul {
  list-style-type: none;
  padding: 0;
}
</style>
