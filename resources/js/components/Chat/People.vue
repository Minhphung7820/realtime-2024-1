<template>
  <div class="people-list bg-gray-50 p-4">
    <h3 class="font-bold text-lg mb-2">Danh sách nhóm và bạn bè</h3>
    <ul>
      <li v-for="(person, index) in people" :key="index" class="people-item flex items-center p-3 border-b cursor-pointer hover:bg-gray-200">
        <!-- Avatar -->
        <img :src="person.avatar" alt="Avatar" class="w-10 h-10 rounded-full mr-3" />

        <!-- Tên và trạng thái -->
        <div class="flex-1">
          <div class="flex justify-between items-center">
            <h4 class="font-semibold">{{ person.name }}</h4>
            <span v-if="!person.isOnline" class="text-xs text-gray-400">{{ person.lastOnline }}</span>
          </div>
        </div>

        <!-- Trạng thái online/offline -->
        <span :class="person.isOnline ? 'bg-green-500' : 'bg-gray-400'" class="status-dot w-3 h-3 rounded-full ml-3"></span>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  data() {
    return {
      people: [
        { name: 'Nhóm A', avatar: 'https://via.placeholder.com/40', isOnline: true, lastOnline: '' },
        { name: 'Nhóm B', avatar: 'https://via.placeholder.com/40', isOnline: false, lastOnline: '5 phút trước' },
        { name: 'Bạn C', avatar: 'https://via.placeholder.com/40', isOnline: true, lastOnline: '' },
        { name: 'Bạn D', avatar: 'https://via.placeholder.com/40', isOnline: false, lastOnline: '1 giờ trước' },
      ],
    };
  },
};
</script>

<style scoped>
.people-item {
  display: flex;
  align-items: center;
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}
</style>
