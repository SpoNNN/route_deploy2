  <template>
    <div class="fixed inset-0 bg-black flex items-center justify-center z-50"
      style="background-color: rgba(0, 0, 0, 0.3)">
      <div class="bg-white p-5 rounded-lg w-96">
        <h2 class="text-lg font-bold" style="font-weight: 600;">Фильтр маршрутов</h2>

        <label class="block mt-3">Минимальная протяженность (км)</label>
        <input v-model="filters.min_distance" type="number" style="border-color: grey; border-radius: 8px;"
          class="border border-black-700 border-color-black-300 p-1 w-full">

        <label class="block mt-3">Максимальная протяженность (км)</label>
        <input v-model="filters.max_distance" type="number" style="border-color: grey; border-radius: 8px;"
          class="border border-black-700  border-color-black-300 p-1 w-full">

        <label class="block mt-3">Макс. кол-во участников</label>
        <input v-model="filters.max_participants" type="number" style="border-color: grey; border-radius: 8px;"
          class="border p-1 w-full">

        <label class="block mt-3">Целевая аудитория</label>
        <select v-model="filters.target_audience" style="border-color:#e9e9ed; border-radius: 8px; background-color: #e9e9ed;"
          class="border p-1 w-full">
          <option :value="''">Все</option>
          <option value="12-18">12-18</option>
          <option value="15-17">15-17</option>
          <option value="16-18">16-18</option>
        </select>

        <label class="block mt-3">Минимальная продолжительность (мин)</label>
        <input v-model="filters.min_duration" type="number" style="border-color: grey; border-radius: 8px;" class="border p-1 w-full">

        <label class="block mt-3">Максимальная продолжительность (мин)</label>
        <input v-model="filters.max_duration" type="number" style="border-color: grey; border-radius: 8px;" class="border p-1 w-full">

        <div class="flex justify-end mt-4 text-white">
          <button @click="applyFilters" :disabled="loading" style="margin-right: 10px;"
            class="bg-blue-500 text-white px-4 py-2 rounded disabled:bg-blue-300">
            {{ loading ? 'Загрузка...' : 'Применить' }}
          </button>
          <button @click="resetFilters" :disabled="loading" style="margin-right: 10px;"
            class="bg-gray-300 text-gray-700 px-4 pr-4 py-2  rounded">Сбросить</button>
          <button @click="$emit('close')" :disabled="loading" style="margin-right: 5px;"
            class="bg-gray-500 text-white px-4 py-2 rounded">Закрыть</button>
        </div>
      </div>
    </div>
  </template>

<script>
import axios from 'axios';
export default {
  name: 'RouteFilter',
  data() {
    return {
      loading: false,
      filters: {
        min_distance: '',
        max_distance: '',
        max_participants: '',
        target_audience: '',
        min_duration: '',
        max_duration: ''
      }
    }
  },
  methods: {
    async applyFilters() {
      this.loading = true;

      try {

        const cleanFilters = {};
        for (const [key, value] of Object.entries(this.filters)) {
          if (value !== '' && value !== null) {
            cleanFilters[key] = value;
          }
        }


        const response = await axios.post('api/routes/filter', cleanFilters);

        if (response.data.success) {

          this.$emit('filtered', response.data.routes);
          this.$emit('close');
        }

      } catch (error) {
        console.error('Ошибка фильтрации:', error);
        alert('Ошибка при применении фильтров');
      } finally {
        this.loading = false;
      }
    },

    resetFilters() {
      this.filters = {
        min_distance: '',
        max_distance: '',
        max_participants: '',
        target_audience: '',
        min_duration: '',
        max_duration: ''
      };


      this.loadAllRoutes();
    },

    async loadAllRoutes() {
      try {
        const response = await axios.post('api/routes/filter', {});
        if (response.data.success) {
          this.$emit('filtered', response.data.routes);
        }
      } catch (error) {
        console.error('Ошибка загрузки маршрутов:', error);
      }
    }
  }
}
</script>