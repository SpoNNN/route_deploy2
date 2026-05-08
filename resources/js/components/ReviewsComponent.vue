<template>
  <div class="reviews-section">

    <button @click="showReviewsModal = true" class="reviews-button">
    
      Отзывы и оценки
      <span v-if="reviewsCount > 0" class="reviews-count">{{ reviewsCount }}</span>
    </button>

        <div v-if="showReviewsModal" class="modal-overlay" @click.self="showReviewsModal = false">
      <div class="modal-content">
       
        <div class="modal-header">
          <h2>Отзывы и оценки</h2>
          <button @click="showReviewsModal = false" class="close-button">✕</button>
        </div>

    
        <div class="rating-summary">
          <div class="average-rating">
            <div class="rating-number">{{ averageRating.toFixed(1) }}</div>
            <div class="stars-display">
           
            </div>
            <div class="reviews-total">{{ reviewsCount }} отзывов</div>
          </div>
        </div>
        
       
        <div v-if="isLoggedIn && !userHasReviewed" class="add-review-form">
          <h3>Оставить отзыв</h3>
          
          <div class="rating-input">
            <label>Ваша оценка:</label>
            <div class="stars-input">
              <span 
                v-for="n in 5" 
                :key="n" 
                class="star-input"
                :class="{ filled: n <= newReview.rating, hover: n <= hoverRating }"
                @click="newReview.rating = n"
                @mouseenter="hoverRating = n"
                @mouseleave="hoverRating = 0"
              >
                ⭐
              </span>
            </div>
          </div>

          <div class="comment-input">
            <label>Ваш отзыв:</label>
            <textarea 
              v-model="newReview.comment" 
              placeholder="Расскажите о впечатлениях от маршрута..."
              rows="4"
              maxlength="500"
            ></textarea>
            <div class="char-count">{{ newReview.comment.length }}/500</div>
          </div>

          <div v-if="reviewError" class="error-message">{{ reviewError }}</div>

          <button 
            @click="submitReview" 
            :disabled="isSubmitting || !newReview.comment.trim()"
            class="submit-button"
          >
            {{ isSubmitting ? 'Отправка...' : 'Оставить отзыв' }}
          </button>
        </div>

        <div v-else-if="!isLoggedIn" class="login-prompt">
          <p>Войдите, чтобы оставить отзыв</p>
        </div>

        <div v-else-if="userHasReviewed" class="already-reviewed">
          <p>Вы уже оставили отзыв на этот маршрут</p>
        </div>

   
        <div class="reviews-list">
          <div v-if="isLoadingReviews" class="loading">Загрузка отзывов...</div>
          
          <div v-else-if="reviews.length === 0" class="no-reviews">
            Пока нет отзывов. Будьте первым!
          </div>

          <div v-else class="review-item" v-for="review in reviews" :key="review.id">
            <div class="review-header">
              <div class="user-avatar">
                <div class="avatar-circle">{{ review.user.name.charAt(0).toUpperCase() }}</div>
              </div>
              <div class="review-info">
                <div class="user-name">{{ review.user.name }}</div>
                <div class="review-date">{{ formatDate(review.created_at) }}</div>
              </div>
              <div class="review-rating">
                <span v-for="n in 5" :key="n" class="star-small" :class="{ filled: n <= review.rating }">
                  ⭐
                </span>
              </div>
            </div>
            <div class="review-comment">{{ review.comment }}</div>
          </div>
        </div>

      
        <div v-if="hasMoreReviews" class="load-more">
          <button @click="loadMoreReviews" :disabled="isLoadingMore" class="load-more-button">
            {{ isLoadingMore ? 'Загрузка...' : 'Читать полностью' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import axios from 'axios'

interface Review {
  id: number
  user_id: number
  route_id: number
  rating: number
  comment: string
  created_at: string
  user: {
    id: number
    name: string
  }
}

const props = defineProps<{
  routeId: number
  isLoggedIn: boolean
}>()

const showReviewsModal = ref(false)
const reviews = ref<Review[]>([])
const isLoadingReviews = ref(false)
const isSubmitting = ref(false)
const reviewError = ref('')
const currentPage = ref(1)
const hasMoreReviews = ref(false)
const isLoadingMore = ref(false)
const hoverRating = ref(0)

const newReview = ref({
  rating: 5,
  comment: ''
})

const reviewsCount = computed(() => reviews.value.length)

const averageRating = computed(() => {
  if (reviews.value.length === 0) return 0
  const sum = reviews.value.reduce((acc, r) => acc + r.rating, 0)
  return sum / reviews.value.length
})

const userHasReviewed = computed(() => {
  if (!props.isLoggedIn) return false
  const userId = JSON.parse(localStorage.getItem('user') || '{}').id
  return reviews.value.some(review => review.user_id === userId)
})

const normalizeReview = (r: any): Review => ({
  id: r.id,
  user_id: r.user_id,
  route_id: r.route_id,
  rating: r.rating ?? r.star_rate,
  comment: r.comment ?? r.text,
  created_at: r.created_at,
  user: r.user
})

const loadReviews = async () => {
  try {
    isLoadingReviews.value = true
    const res = await axios.get(`/api/routes/${props.routeId}/reviews?page=${currentPage.value}`)

    const items = res.data.data.map((r: any) => normalizeReview(r))

    if (currentPage.value === 1) reviews.value = items
    else reviews.value = [...reviews.value, ...items]

    hasMoreReviews.value = res.data.current_page < res.data.last_page
  } catch (err) {
    console.error(err)
  } finally {
    isLoadingReviews.value = false
    isLoadingMore.value = false
  }
}

const loadMoreReviews = async () => {
  isLoadingMore.value = true
  currentPage.value++
  await loadReviews()
}

const submitReview = async () => {
  if (!newReview.value.comment.trim()) {
    reviewError.value = 'Введите текст'
    return
  }

  try {
    isSubmitting.value = true

    const res = await axios.post(`/api/routes/${props.routeId}/reviews`, {
      rating: newReview.value.rating,
      comment: newReview.value.comment
    })

    reviews.value.unshift(normalizeReview(res.data.data))

    newReview.value = { rating: 5, comment: '' }

    alert('Спасибо за отзыв!')
  } catch (e: any) {
    reviewError.value = e.response?.data?.message || 'Ошибка'
  } finally {
    isSubmitting.value = false
  }
}

watch(() => showReviewsModal.value, (open) => {
  if (open && reviews.value.length === 0) loadReviews()
})

const formatDate = (d: string) => {
  const date = new Date(d)
  return `${date.getDate().toString().padStart(2, '0')}.${(date.getMonth()+1)
    .toString().padStart(2,'0')}.${date.getFullYear()}`
}
</script>


<style scoped>
.reviews-section {
  margin: 10px;
}

.reviews-button {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 20px;
 
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  
}

.reviews-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.6);
}

.reviews-icon {
  font-size: 20px;
}

.reviews-count {
  background: rgba(255, 255, 255, 0.3);
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 14px;
}


.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 20px;
  width: 100%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 24px 16px;
  border-bottom: 2px solid #e5e7eb;
  position: sticky;
  top: 0;
  background: white;
  z-index: 1;
}

.modal-header h2 {
  margin: 0;
  font-size: 24px;
  color: #1f2937;
}

.close-button {
  background: none;
  border: none;
  font-size: 28px;
  color: #6b7280;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s;
}

.close-button:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.rating-summary {
  padding: 24px;
  background: linear-gradient(135deg, #764ba2 0%, #764ba2 100%);
  color: white;
  text-align: center;
}

.average-rating {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.rating-number {
  font-size: 48px;
  font-weight: bold;
}

.stars-display {
  font-size: 24px;
  display: flex;
  gap: 4px;
}



.reviews-total {
  font-size: 16px;
  opacity: 0.9;
}


.add-review-form {
  padding: 24px;
  border-bottom: 2px solid #e5e7eb;
}

.add-review-form h3 {
  margin: 0 0 20px 0;
  color: #1f2937;
  font-size: 20px;
}

.rating-input {
  margin-bottom: 20px;
}

.rating-input label {
  display: block;
  margin-bottom: 8px;
  color: #4b5563;
  font-weight: 500;
}

.stars-input {
  display: flex;
  gap: 8px;
  font-size: 32px;
}

.star-input {
  cursor: pointer;
  filter: grayscale(100%);
  opacity: 0.3;

}

.star-input.filled,
.star-input.hover {
  filter: grayscale(0%);
  opacity: 1;
  transform: scale(1.1);
}

.comment-input {
  margin-bottom: 16px;
}

.comment-input label {
  display: block;
  margin-bottom: 8px;
  color: #4b5563;
  font-weight: 500;
}

.comment-input textarea {
  width: 100%;
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  resize: vertical;
  transition: border-color 0.2s;
}

.comment-input textarea:focus {
  outline: none;
  border-color: #667eea;
}

.char-count {
  text-align: right;
  font-size: 12px;
  color: #9ca3af;
  margin-top: 4px;
}

.submit-button {
  width: 100%;
  padding: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.submit-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.submit-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.error-message {
  background: #fee;
  color: #c33;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 14px;
}

.login-prompt,
.already-reviewed {
  padding: 24px;
  text-align: center;
  color: #6b7280;
  border-bottom: 2px solid #e5e7eb;
}


.reviews-list {
  padding: 24px;
}

.loading,
.no-reviews {
  text-align: center;
  padding: 40px;
  color: #9ca3af;
  font-size: 16px;
}

.review-item {
  padding: 20px;
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  margin-bottom: 16px;
  transition: all 0.2s;
}

.review-item:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.review-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.user-avatar {
  flex-shrink: 0;
}

.avatar-circle {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: bold;
}

.review-info {
  flex: 1;
}

.user-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 16px;
}

.review-date {
  font-size: 14px;
  color: #9ca3af;
  margin-top: 2px;
}

.review-rating {
  display: flex;
  gap: 2px;
  font-size: 16px;
}

.star-small {
  filter: grayscale(100%);
  opacity: 0.3;
}

.star-small.filled {
  filter: grayscale(0%);
  opacity: 1;
}

.review-comment {
  color: #4b5563;
  line-height: 1.6;
  font-size: 15px;
}


.load-more {
  padding: 0 24px 24px;
  text-align: center;
}

.load-more-button {
  padding: 12px 32px;
  background: white;
  color: #667eea;
  border: 2px solid #667eea;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.load-more-button:hover:not(:disabled) {
  background: #667eea;
  color: white;
}

.load-more-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}


@media (max-width: 768px) {
  .modal-content {
    max-height: 95vh;
  }

  .rating-number {
    font-size: 36px;
  }

  .stars-display {
    font-size: 20px;
  }
}
</style>