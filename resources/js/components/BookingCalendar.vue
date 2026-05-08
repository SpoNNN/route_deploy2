<template>
  <a-modal
    :open="visible"
    title="Календарь событий"
    :footer="null"
    width="900px"
    :centered="true"
    @cancel="handleClose"
  >
    <a-spin :spinning="isLoading">
      <div class="booking-calendar">
        <div class="calendar-container">
          <div class="calendar-header">
            <a-button @click="previousMonth" type="text" class="nav-button">
              <LeftOutlined />
            </a-button>
            <h2 class="current-month">{{ currentMonthName }} {{ currentYear }}</h2>
            <a-button @click="nextMonth" type="text" class="nav-button">
              <RightOutlined />
            </a-button>
          </div>

          <div class="calendar-grid">
            <div class="weekday-header" v-for="day in weekdays" :key="day">
              {{ day }}
            </div>

            <div
              v-for="(day, index) in calendarDays"
              :key="index"
              class="calendar-day"
              :class="{
                'other-month': !day.isCurrentMonth,
                'today': day.isToday,
                'selected': day.isSelected,
                'has-event': day.hasEvent
              }"
              @click="selectDate(day)"
            >
              <span class="day-number">{{ day.day }}</span>
              <div v-if="day.hasEvent" class="event-indicator"></div>
            </div>
          </div>
        </div>

        <div class="event-details" v-if="selectedEvent">
          <div class="event-card">
            <div class="event-date">
              <div class="event-day">{{ selectedEvent.dayNumber }}</div>
              <div class="event-month">{{ selectedEvent.monthName }}</div>
              <div class="event-weekday">{{ selectedEvent.weekday }}</div>
            </div>

            <div class="event-info">
              <h3 class="event-title">{{ selectedEvent.title }}</h3>
              
              <div class="event-time">
                <div class="time-label">Начало</div>
                <div class="time-value">{{ selectedEvent.startTime }}</div>
              </div>

              <div class="event-capacity">
                <div class="capacity-label">Количество мест:</div>
                <div class="capacity-value">{{ selectedEvent.bookedSeats }}/{{ selectedEvent.totalSeats }}</div>
              </div>

              <div class="event-location">
                <EnvironmentOutlined />
                <span>{{ selectedEvent.location }}</span>
              </div>

              <div class="event-description">
                {{ selectedEvent.description }}
              </div>

              <a-button 
                type="primary" 
                size="large" 
                block
                class="booking-button"
                :disabled="selectedEvent.bookedSeats >= selectedEvent.totalSeats"
                @click="showBookingForm"
              >
                {{ selectedEvent.bookedSeats >= selectedEvent.totalSeats ? 'Мест нет' : 'Записаться' }}
              </a-button>
            </div>
          </div>
        </div>

        <div v-else class="no-event-selected">
          <CalendarOutlined style="font-size: 48px; color: #d9d9d9; margin-bottom: 16px;" />
          <p>Выберите дату с событием</p>
        </div>
      </div>
    </a-spin>
  </a-modal>

  <a-modal
    :open="bookingFormVisible"
    :footer="null"
    width="500px"
    :centered="true"
    @cancel="closeBookingForm"
  >
    <div class="booking-form-container">
      <h2 class="form-title">Зарегистрироваться на научно-образовательный маршрут</h2>

      <a-form
        :model="formState"
        layout="vertical"
        @finish="onSubmitBooking"
        class="booking-form"
      >
        <a-form-item
          name="firstName"
          :rules="[{ required: true, message: 'Введите имя' }]"
        >
          <label class="form-label">
            Имя<span class="required-star">*</span>
          </label>
          <a-input 
            v-model:value="formState.firstName" 
            placeholder="" 
            size="large"
            class="form-input"
          />
        </a-form-item>

        <a-form-item
          name="lastName"
          :rules="[{ required: true, message: 'Введите фамилию' }]"
        >
          <label class="form-label">
            Фамилия<span class="required-star">*</span>
          </label>
          <a-input 
            v-model:value="formState.lastName" 
            placeholder="" 
            size="large"
            class="form-input"
          />
        </a-form-item>

        <a-form-item
          name="seats"
          :rules="[{ required: true, message: 'Введите количество мест' }]"
        >
          <label class="form-label">
            Количество мест<span class="required-star">*</span>
          </label>
          <a-input-number 
            v-model:value="formState.seats" 
            :min="1"
            :max="10"
            size="large"
            class="form-input"
            style="width: 100%"
          />
        </a-form-item>

        <a-form-item
          name="phone"
          :rules="[
            { required: true, message: 'Введите телефон' },
            { pattern: /^[0-9+\-\s()]+$/, message: 'Некорректный формат телефона' }
          ]"
        >
          <label class="form-label">
            Телефон<span class="required-star">*</span>
          </label>
          <a-input 
            v-model:value="formState.phone" 
            placeholder="" 
            size="large"
            class="form-input"
          />
        </a-form-item>

        <a-form-item
          name="email"
          :rules="[
            { required: true, message: 'Введите email' },
            { type: 'email', message: 'Некорректный email' }
          ]"
        >
          <label class="form-label">
            Email<span class="required-star">*</span>
          </label>
          <a-input 
            v-model:value="formState.email" 
            placeholder="" 
            size="large"
            class="form-input"
          />
        </a-form-item>

        <a-form-item>
          <label class="form-label">Дата</label>
          <a-input 
            :value="selectedEvent ? dayjs(selectedEvent.date).format('DD.MM.YYYY') : ''"
            disabled
            size="large"
            class="form-input"
          />
        </a-form-item>

        <a-form-item style="margin-bottom: 0; margin-top: 24px;">
          <a-button 
            type="primary" 
            html-type="submit" 
            size="large"
            block
            class="submit-button"
            :loading="isSubmitting"
          >
            Отправить
          </a-button>
        </a-form-item>
      </a-form>
    </div>
  </a-modal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { LeftOutlined, RightOutlined, EnvironmentOutlined, CalendarOutlined } from '@ant-design/icons-vue'
import { message } from 'ant-design-vue'
import dayjs from 'dayjs'
import 'dayjs/locale/ru'
import axios from 'axios'

dayjs.locale('ru')

interface Event {
  id: number
  date: string
  title: string
  startTime: string
  bookedSeats: number
  totalSeats: number
  location: string
  description: string
  routeId: number
}

interface CalendarDay {
  day: number
  date: string
  isCurrentMonth: boolean
  isToday: boolean
  isSelected: boolean
  hasEvent: boolean
}

interface BookingFormState {
  firstName: string
  lastName: string
  seats: number
  phone: string
  email: string
  userId: number
}

const props = defineProps<{
  visible: boolean
  routeId: number
  routeTitle: string
  userId: number  
}>()

const emit = defineEmits(['update:visible', 'book', 'booking-success'])

const currentDate = ref(dayjs())
const selectedDate = ref<string | null>(null)
const bookingFormVisible = ref(false)
const isSubmitting = ref(false)
const isLoading = ref(false)
const events = ref<Event[]>([])

const formState = ref<BookingFormState>({
  firstName: '',
  lastName: '',
  seats: 1,
  phone: '',
  email: '',
  userId: 0  
})

const weekdays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']

const monthNames = [
  'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
  'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
]

const currentMonthName = computed(() => monthNames[currentDate.value.month()])
const currentYear = computed(() => currentDate.value.year())

const loadEvents = async () => {
  if (!props.routeId) return

  try {
    isLoading.value = true
    const response = await axios.get(`/api/routes/${props.routeId}/events`)
    
    if (response.data.success) {
      events.value = response.data.data
    } else {
      message.error('Ошибка загрузки событий')
    }
  } catch (error) {
    console.error('Ошибка загрузки событий:', error)
    message.error('Не удалось загрузить события')
  } finally {
    isLoading.value = false
  }
}

const calendarDays = computed(() => {
  const days: CalendarDay[] = []
  const firstDay = currentDate.value.startOf('month')
  const lastDay = currentDate.value.endOf('month')
  
  let dayOfWeek = firstDay.day()
  if (dayOfWeek === 0) dayOfWeek = 7
  
  const prevMonthDays = dayOfWeek - 1
  const prevMonthStart = firstDay.subtract(prevMonthDays, 'day')
  
  for (let i = 0; i < prevMonthDays; i++) {
    const date = prevMonthStart.add(i, 'day')
    days.push({
      day: date.date(),
      date: date.format('YYYY-MM-DD'),
      isCurrentMonth: false,
      isToday: date.isSame(dayjs(), 'day'),
      isSelected: date.format('YYYY-MM-DD') === selectedDate.value,
      hasEvent: hasEvent(date.format('YYYY-MM-DD'))
    })
  }
  
  const daysInMonth = lastDay.date()
  for (let i = 1; i <= daysInMonth; i++) {
    const date = firstDay.date(i)
    days.push({
      day: i,
      date: date.format('YYYY-MM-DD'),
      isCurrentMonth: true,
      isToday: date.isSame(dayjs(), 'day'),
      isSelected: date.format('YYYY-MM-DD') === selectedDate.value,
      hasEvent: hasEvent(date.format('YYYY-MM-DD'))
    })
  }
  
  const totalCells = 42
  const remainingCells = totalCells - days.length
  const nextMonthStart = lastDay.add(1, 'day')
  
  for (let i = 0; i < remainingCells; i++) {
    const date = nextMonthStart.add(i, 'day')
    days.push({
      day: date.date(),
      date: date.format('YYYY-MM-DD'),
      isCurrentMonth: false,
      isToday: date.isSame(dayjs(), 'day'),
      isSelected: date.format('YYYY-MM-DD') === selectedDate.value,
      hasEvent: hasEvent(date.format('YYYY-MM-DD'))
    })
  }
  
  return days
})

const hasEvent = (date: string) => {
  return events.value.some(event => event.date === date && event.routeId === props.routeId)
}

const selectedEvent = computed(() => {
  if (!selectedDate.value) return null
  
  const event = events.value.find(e => e.date === selectedDate.value && e.routeId === props.routeId)
  if (!event) return null
  
  const date = dayjs(selectedDate.value)
  
  return {
    ...event,
    dayNumber: date.format('DD'),
    monthName: monthNames[date.month()],
    weekday: date.format('dddd')
  }
})

const previousMonth = () => {
  currentDate.value = currentDate.value.subtract(1, 'month')
}

const nextMonth = () => {
  currentDate.value = currentDate.value.add(1, 'month')
}

const selectDate = (day: CalendarDay) => {
  if (!day.hasEvent) return
  selectedDate.value = day.date
}

const handleClose = () => {
  emit('update:visible', false)
  selectedDate.value = null
}

const showBookingForm = () => {
  bookingFormVisible.value = true
}

const closeBookingForm = () => {
  bookingFormVisible.value = false
  formState.value = {
    firstName: '',
    lastName: '',
    seats: 1,
    phone: '',
    email: '',
    userId: props.userId
  }
}

const onSubmitBooking = async () => {
  if (!selectedEvent.value) return

  try {
    isSubmitting.value = true

    const bookingData = {
      eventId: selectedEvent.value.id,
      firstName: formState.value.firstName,
      lastName: formState.value.lastName,
      seats: formState.value.seats,
      phone: formState.value.phone,
      email: formState.value.email,
      userId: props.userId,   
      eventDate: selectedEvent.value.date,
      routeId: props.routeId
    }

    const response = await axios.post('/api/bookings', bookingData)

    if (response.data.success) {
      message.success('Бронирование успешно создано!')
      
     
      const eventIndex = events.value.findIndex(e => e.id === selectedEvent.value!.id)
      if (eventIndex !== -1) {
        events.value[eventIndex].bookedSeats = response.data.data.bookedSeats
      }

      emit('booking-success', response.data.data)
      emit('book', bookingData)

      closeBookingForm()
      handleClose()
    } else {
      message.error(response.data.message || 'Ошибка при создании бронирования')
    }

  } catch (error: any) {
    console.error('Ошибка бронирования:', error)
    
    if (error.response?.data?.message) {
      message.error(error.response.data.message)
    } else if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat()
      message.error(errors[0] as string || 'Ошибка валидации')
    } else {
      message.error('Произошла ошибка при бронировании')
    }
  } finally {
    isSubmitting.value = false
  }
}


watch(() => props.visible, (newVal) => {
  if (newVal) {
    currentDate.value = dayjs()
    selectedDate.value = null
    loadEvents()
  }
})

watch(() => props.routeId, (newVal) => {
  if (newVal && props.visible) {
    loadEvents()
  }
})
</script>

<style scoped>
.booking-calendar {
  display: flex;
  gap: 24px;
  min-height: 500px;
}

.calendar-container {
  flex: 1;
}

.calendar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.current-month {
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}

.nav-button {
  font-size: 18px;
  color: #1890ff;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}

.weekday-header {
  text-align: center;
  font-weight: 600;
  padding: 12px 0;
  color: #666;
  font-size: 14px;
}

.calendar-day {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  cursor: pointer;
  position: relative;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.calendar-day:hover {
  background-color: #f5f5f5;
}

.calendar-day.other-month {
  opacity: 0.3;
  pointer-events: none;
}

.calendar-day.selected {
  background-color: #1890ff;
  color: white;
}

.calendar-day.has-event {
  background-color: #ffa940;
  border-color: #ffa940;
  border-radius: 35px;
  cursor: pointer;
}

.calendar-day.has-event:hover {
  background-color: #ffe7ba;
}

.calendar-day.selected.has-event {
  background-color: #1890ff;
  border-radius: 35px;
}

.day-number {
  font-size: 14px;
  font-weight: 500;
}

.event-indicator {
  width: 6px;
  height: 6px;
  background-color: #ffa940;
  border-radius: 50%;
  position: absolute;
  bottom: 4px;
}

.calendar-day.selected .event-indicator {
  background-color: white;
}

.event-details {
  width: 340px;
  flex-shrink: 0;
}

.event-card {
  background: #025fb6;
  border-radius: 16px;
  padding: 24px;
  color: white;
}

.event-date {
  text-align: center;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.event-day {
  font-size: 48px;
  font-weight: bold;
  line-height: 1;
}

.event-month {
  font-size: 18px;
  margin-top: 4px;
  text-transform: capitalize;
}

.event-weekday {
  font-size: 14px;
  margin-top: 4px;
  opacity: 0.9;
  text-transform: capitalize;
}

.event-info {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.event-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0;
  color: white;
}

.event-time {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.time-label {
  font-size: 12px;
  opacity: 0.8;
}

.time-value {
  font-size: 20px;
  font-weight: 600;
}

.event-capacity {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.capacity-label {
  font-size: 12px;
  opacity: 0.8;
}

.capacity-value {
  font-size: 16px;
  font-weight: 600;
}

.event-location {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 14px;
  line-height: 1.5;
}

.event-description {
  font-size: 14px;
  line-height: 1.5;
}

.booking-button {
  margin-top: 8px;
  height: 44px;
  font-weight: 600;
  background-color: #FFB800;
  border-color: #FFB800;
  color: #32368E;
}

.booking-button:hover:not(:disabled) {
  background-color: #ffa500;
  border-color: #ffa500;
}

.booking-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.no-event-selected {
  width: 340px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #999;
  text-align: center;
}

.booking-form-container {
  position: relative;
  padding: 20px 0;
}

.form-title {
  font-size: 18px;
  font-weight: 600;
  text-align: center;
  margin-bottom: 24px;
  color: #000;
}

.booking-form {
  margin-top: 20px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  color: #000;
  font-weight: 500;
}

.required-star {
  color: #ff4d4f;
  margin-left: 4px;
}

.form-input {
  border-radius: 8px;
  border: 1px solid #d9d9d9;
}

.form-input:focus,
.form-input:hover {
  border-color: #4096ff;
}

.submit-button {
  height: 48px;
  font-size: 16px;
  font-weight: 600;
  background: #405394;
  border-color: #405394;
  border-radius: 8px;
}

.submit-button:hover:not(:disabled) {
  background: #32407a;
  border-color: #32407a;
}
</style>