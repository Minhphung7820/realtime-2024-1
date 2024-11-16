<template>
  <div class="people-list bg-gray-50 p-2 sm:p-4">
    <h3 class="font-bold text-lg mb-2">Danh sách nhóm và bạn bè</h3>
    <ul>
      <li v-for="(person, index) in people" :key="index" class="people-item flex items-center p-2 sm:p-3 border-b cursor-pointer hover:bg-gray-200">
        <img :src="person.avatar" alt="Avatar" class="avatar w-8 h-8 sm:w-10 sm:h-10 rounded-full mr-2 sm:mr-3" />
        <div class="flex-1 max-w-xs">
          <div class="flex justify-between items-center w-full">
            <h4 class="font-semibold text-sm sm:text-base truncate">{{ person.name }}</h4>
            <span v-if="!person.isOnline" class="text-xs text-gray-400 last-online">{{ person.last_active_string }}</span>
          </div>
        </div>
        <span :class="person.isOnline ? 'bg-green-500' : 'bg-gray-400'" class="status-dot"></span>
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  inject: ['$axios', '$socket'],
  data() {
    return {
      people: [],
      updateLastActiveInterval : null
    };
  },
  async mounted() {
    await this.getPeople();
    await this.fetchOnlineUsers();
    // Lắng nghe sự kiện từ WebSocket
    this.$socket.on('user_list',this.handleUserWithStatus);
    this.$socket.on('user_disconnect_list', this.handleUserWithStatus);
    // Chạy hàm cập nhật last_active mỗi giây
    this.updateLastActiveInterval = setInterval(() => {
        this.updateLastActive();
    }, 1000);
  },
  beforeUnmount() {
    // Dừng interval khi component bị hủy
    clearInterval(this.updateLastActiveInterval);
  },
  methods: {
      async fetchOnlineUsers() {
        try {
          const response = await this.$axios.get('http://localhost:6060/api/online-users');
          const onlineUsers = response.data.data;

          // Cập nhật trạng thái online vào mảng people
          onlineUsers.forEach(user => {
            const matchingPerson = this.people.find(person => person.id === parseInt(user.userID));
            if (matchingPerson) {
              matchingPerson.isOnline = user.isOnline;
            }
          });
        } catch (error) {
          console.error('Failed to fetch online users:', error);
        }
    },
    async getPeople() {
      try {
        let limitPeople = 10;
        const getPeople = await this.$axios.get(`/api/get-people?limit=${limitPeople}`);
        this.people = getPeople.data.data.map(person => ({
          ...person,
          isOnline: false, // Mặc định offline
          last_active_string: this.formatTimeDifference(person.last_active)
        }));
      } catch (error) {
        console.log('Failed get data:', error);
      }
    },
    handleUserWithStatus(user) {
      const matchingPerson = this.people.find(person => person.id === parseInt(user.userID));
      if (matchingPerson) {
        matchingPerson.isOnline = user.online;
        matchingPerson.last_active = user.last_active;
      }
    },
    // Hàm tính thời gian trước đó
    formatTimeDifference(lastActive) {
      const now = new Date();
      const lastActiveDate = new Date(lastActive);
      const diffSeconds = Math.floor((now - lastActiveDate) / 1000); // Chênh lệch giây

      if (diffSeconds < 60) {
        return 'Vừa truy cập';
      } else if (diffSeconds < 3600) {
        const minutes = Math.floor(diffSeconds / 60);
        return `${minutes} phút trước`;
      } else if (diffSeconds < 86400) {
        const hours = Math.floor(diffSeconds / 3600);
        return `${hours} giờ trước`;
      } else if (diffSeconds < 604800) {
        const days = Math.floor(diffSeconds / 86400);
        return `${days} ngày trước`;
      } else if (diffSeconds < 2592000) {
        const weeks = Math.floor(diffSeconds / 604800);
        return `${weeks} tuần trước`;
      } else {
        const months = Math.floor(diffSeconds / 2592000);
        return `${months} tháng trước`;
      }
    },
    // Hàm cập nhật last_active cho từng người online
    updateLastActive() {
      this.people.forEach(person => {
        if (person.isOnline && person.last_active) {
          person.last_active_string = this.formatTimeDifference(person.last_active);
        }
      });
    }
  }
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
