<template>
  <div class="people-list bg-gray-50 p-2 sm:p-4">
    <h3 class="font-bold text-lg mb-2">Mọi người</h3>

    <!-- Tabs -->
    <div class="tabs flex border-b mb-4">
      <button
        class="tab px-4 py-2"
        :class="{ 'border-b-2 border-blue-500 font-bold': activeTab === 'friends' }"
        @click="activeTab = 'friends'"
      >
        <UsersIcon class="w-6 h-6 text-blue-500" />
      </button>
      <button
        class="tab px-4 py-2 relative"
        :class="{ 'border-b-2 border-blue-500 font-bold': activeTab === 'requests' }"
        @click="activeTab = 'requests'"
      >
        <UserPlusIcon class="w-6 h-6 text-blue-500" />
        <span
          v-if="countRequestFriend > 0"
          class="absolute top-0 right-0 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full"
        >
          {{ countRequestFriend > 9 ? '9+' : countRequestFriend }}
        </span>
      </button>
      <button
        class="tab px-4 py-2"
        :class="{ 'border-b-2 border-blue-500 font-bold': activeTab === 'search' }"
        @click="activeTab = 'search'"
      >
        <UserGroupIcon class="w-6 h-6 text-blue-500" />
      </button>
    </div>

    <!-- Nội dung các tab -->
    <div v-if="activeTab === 'friends'">
      <!-- Danh sách bạn bè (cũ) -->
      <div v-if="isLoading" class="loading-container">
        <div class="spinner"></div>
      </div>
      <ul v-else>
        <li
          v-for="(person, index) in people"
          :key="index"
          class="people-item flex items-center p-2 sm:p-3 border-b cursor-pointer hover:bg-gray-200"
          @click="openChat(person.id, 'private')"
        >
          <img :src="person.avatar" alt="Avatar" class="avatar w-8 h-8 sm:w-10 sm:h-10 rounded-full mr-2 sm:mr-3" />
          <div class="flex-1 max-w-xs">
            <div class="flex justify-between items-center w-full">
              <h4 class="font-semibold text-sm sm:text-base truncate">{{ person.name }}</h4>
              <span
                v-if="!person.isOnline"
                class="text-xs text-gray-400 last-online"
              >
                {{ person.last_active_string }}
              </span>
            </div>
          </div>
          <span
            :class="person.isOnline ? 'bg-green-500' : 'bg-gray-400'"
            class="status-dot"
          ></span>
        </li>
      </ul>
    </div>

    <div v-else-if="activeTab === 'requests'">
      <!-- UI Yêu cầu kết bạn -->
      <ul>
        <li
          v-for="(request, index) in friendRequests"
          :key="index"
          class="people-item flex items-center p-2 sm:p-3 border-b cursor-pointer hover:bg-gray-200"
        >
          <img
            :src="request.avatar"
            alt="Avatar"
            class="avatar w-8 h-8 sm:w-10 sm:h-10 rounded-full mr-2 sm:mr-3"
          />
          <div class="flex-1">
            <h4 class="font-semibold text-sm sm:text-base truncate">
              {{ request.name }}
            </h4>
            <div class="flex space-x-2 mt-1">
              <button @click="changeStatusRequestFriend(request.id, 'accepted')" class="px-2 py-1 bg-blue-500 text-white rounded text-xs">Chấp nhận</button>
              <button @click="changeStatusRequestFriend(request.id, 'blocked')" class="px-2 py-1 bg-red-500 text-white rounded text-xs">Từ chối</button>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <div v-else-if="activeTab === 'search'">
      <!-- UI Tìm kiếm -->
      <div class="search-container mb-4">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Tìm kiếm người dùng..."
          class="w-full px-3 py-2 border rounded"
          @keyup.enter="getOtherUser"
        />
      </div>
      <div v-if="isLoading" class="loading-container">
        <div class="spinner"></div>
      </div>
      <ul v-else>
        <li
          v-for="(user, index) in searchResults"
          :key="index"
          class="people-item flex items-center p-2 sm:p-3 border-b cursor-pointer hover:bg-gray-200"
        >
          <img
            :src="user.avatar"
            alt="Avatar"
            class="avatar w-8 h-8 sm:w-10 sm:h-10 rounded-full mr-2 sm:mr-3"
          />
          <div class="flex-1">
            <h4 class="font-semibold text-sm sm:text-base truncate">{{ user.name }}</h4>
            <button v-if="user.status_friend === 'not_friend'"
              @click="sendFriendRequest(user.id)"
              class="px-2 py-1 bg-blue-500 text-white rounded text-xs"
            >
              Kết bạn
            </button>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import { UserPlusIcon,UsersIcon,UserGroupIcon } from '@heroicons/vue/24/solid';

export default {
  components: {
    UserPlusIcon,UsersIcon,UserGroupIcon
  },
  inject: ['$axios', '$socket','$userProfile'],
  data() {
    return {
      people: [],
      updateLastActiveInterval : null,
      isLoading: true,
      activeTab: 'friends',
      friendRequests:[], // Danh sách yêu cầu kết bạn (mẫu)
      searchQuery: '', // Dữ liệu input tìm kiếm
      searchResults:[], // Kết quả tìm kiếm (mẫu)
      countRequestFriend : 0
    };
  },
  async mounted() {
    await this.getPeople();
    await this.fetchOnlineUsers();
    await this.getRequestFriend();
    // Lắng nghe sự kiện từ WebSocket
    this.$socket.on('user_list',this.handleUserWithStatus);
    this.$socket.on('user_disconnect_list', this.handleUserWithStatus);
    this.$socket.on('receive_friend_request', (data) => {
        const { sender_id, receiver_id } = data;
        this.getRequestFriend();
        this.countRequestFriend ++;
    });
    // Chạy hàm cập nhật last_active mỗi giây
    this.updateLastActiveInterval = setInterval(() => {
        this.updateLastActive();
    }, 1000);
    this.isLoading = false;
  },
  beforeUnmount() {
    // Dừng interval khi component bị hủy
    clearInterval(this.updateLastActiveInterval);
  },
  methods: {
    openChat(userId, type) {
      if (this.$parent.dataMessage.id === userId && this.$parent.dataMessage.type === type) {
        // Nếu người dùng đang mở chính họ, không làm gì cả
        return;
      }
      this.$emit('open-chat', userId, type); // Phát sự kiện open-chat lên cha
    },
    async changeStatusRequestFriend(requestId,status){
         try {
          await this.$axios.put(`/api/change-request-friend/${requestId}`,{
            status
          });
        } catch (error) {
          console.error('Failed to fetch online users:', error);
        }
    },
    async getRequestFriend()
    {
        try {
          const response = await this.$axios.get(`/api/get-request-friend`);
          this.friendRequests = response.data.data;
          this.countRequestFriend = response.data.total;
        } catch (error) {
          console.error('Failed to fetch online users:', error);
        }
    },
    async sendFriendRequest(userID)
    {
      try {
        this.isLoading = true;
        await this.$axios.post(`/api/send-request-friend`, {
             friend_id : userID
        });
        const data = {
            sender_id: this.$userProfile.id,
            receiver_id: userID,
        };
        this.$socket.emit('send_friend_request', data);
        this.isLoading = false;
      } catch (error) {
        console.error('Failed to fetch online users:', error);
      }
    },
    async getOtherUser()
    {
      try {
        this.isLoading = true;
        const response = await this.$axios.get(`/api/get-other-user`, {
          params: {
            keyword: this.searchQuery
          }
        });
        this.searchResults = response.data.data;
        this.isLoading = false;
      } catch (error) {
        console.error('Failed to fetch online users:', error);
      }
    },
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

        const firstTenPeople = getPeople.data.data.slice(0, 10).map(person => ({
          ...person,
          isOnline: false, // Mặc định offline
          last_active_string:person.last_active ? this.formatTimeDifference(person.last_active) : null
        }));

        const remainingPeople = getPeople.data.data.slice(10).map(person => ({
          ...person,
          isOnline: false,
          last_active_string: null // Không áp dụng format cho các phần tử sau
        }));

      this.people = [...firstTenPeople, ...remainingPeople];
      } catch (error) {
        console.log('Failed get data:', error);
      }
    },
    handleUserWithStatus(user) {
      const matchingPerson = this.people.find(person => person.id === parseInt(user.userID));
      if (matchingPerson) {
        matchingPerson.isOnline = user.online;
        matchingPerson.last_active = user.last_active;
        matchingPerson.last_active_string = this.formatTimeDifference(user.last_active)
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
      this.people.slice(0, 10).forEach(person => {
        if (!person.isOnline && person.last_active) {
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

.tab {
  padding: 8px 16px;
  cursor: pointer;
  transition: all 0.3s ease;
}
.tab:hover {
  background-color: #f0f0f0;
}
.tab.border-b-2 {
  border-bottom: 2px solid #3b82f6;
  font-weight: bold;
}
.absolute {
  position: absolute;
}
.bg-red-500 {
  background-color: #ef4444;
}
.people-item {
  display: flex;
  align-items: center;
}
.avatar {
  margin-right: 8px;
}
.last-online {
  margin-right: 8px;
}
.status-dot {
  width: 8px;
  height: 8px;
  margin-left: auto;
  border-radius: 50%;
}
</style>
