<template>
  <div class="people-list bg-gray-50 p-4">
    <h3 class="font-bold text-lg mb-2">Danh sách nhóm và bạn bè</h3>
    <ul>
      <li v-for="(person, index) in people" :key="index" class="p-2 border-b cursor-pointer hover:bg-gray-200">
        {{ person.name }}
      </li>
    </ul>
  </div>
</template>

<script>
export default {
  data() {
    return {
      people: [
        { name: 'Nhóm A' },
        { name: 'Nhóm B' },
        { name: 'Bạn C' },
        { name: 'Bạn D' },
      ],
    };
  },
};
</script>

<style scoped>
.people-list ul {
  list-style-type: none;
  padding: 0;
}
</style>
