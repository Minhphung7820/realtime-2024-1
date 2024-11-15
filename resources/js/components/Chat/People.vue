<template>
  <div class="people-list bg-gray-50 p-2 sm:p-4">
    <h3 class="font-bold text-lg mb-2">Danh sách nhóm và bạn bè</h3>
    <ul>
      <li v-for="(person, index) in people" :key="index" class="people-item flex items-center p-2 sm:p-3 border-b cursor-pointer hover:bg-gray-200">
        <img :src="person.avatar" alt="Avatar" class="avatar w-8 h-8 sm:w-10 sm:h-10 rounded-full mr-2 sm:mr-3" />
        <div class="flex-1 max-w-xs">
          <div class="flex justify-between items-center w-full">
            <h4 class="font-semibold text-sm sm:text-base truncate">{{ person.name }}</h4>
            <span v-if="!person.isOnline" class="text-xs text-gray-400 last-online">{{ person.lastOnline }}</span>
          </div>
        </div>
        <span :class="person.isOnline ? 'bg-green-500' : 'bg-gray-400'" class="status-dot"></span>
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

.avatar {
  margin-right: 8px;
}

.last-online {
  margin-right: 8px; /* Tạo khoảng cách giữa lastOnline và status-dot */
}

.status-dot {
  width: 8px;
  height: 8px;
  margin-left: auto;
  border-radius: 50%;
}
</style>
