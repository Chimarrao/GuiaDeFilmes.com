<template>
  <div class="rating-badge" :style="{ '--rating-color': ratingColor }">
    <svg class="rating-circle" viewBox="0 0 36 36">
      <!-- Background circle -->
      <path
        class="circle-bg"
        d="M18 2.0845
          a 15.9155 15.9155 0 0 1 0 31.831
          a 15.9155 15.9155 0 0 1 0 -31.831"
      />
      <!-- Progress circle -->
      <path
        class="circle-progress"
        :stroke-dasharray="`${percentage}, 100`"
        d="M18 2.0845
          a 15.9155 15.9155 0 0 1 0 31.831
          a 15.9155 15.9155 0 0 1 0 -31.831"
      />
    </svg>
    <div class="rating-text">
      <span class="rating-number">{{ displayScore }}</span>
      <span class="rating-symbol">%</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'RatingBadge',
  props: {
    score: {
      type: Number,
      required: true,
      validator: (value) => value >= 0 && value <= 10
    }
  },
  computed: {
    percentage() {
      return (this.score * 10).toFixed(0)
    },
    displayScore() {
      return (this.score * 10).toFixed(0)
    },
    ratingColor() {
      const score = this.score * 10
      if (score >= 80) return '#00b050'
      if (score >= 60) return '#ffc000'
      return '#c00000'
    }
  }
}
</script>

<style scoped>
.rating-badge {
  position: relative;
  width: 50px;
  height: 50px;
  display: inline-block;
}

.rating-circle {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.circle-bg {
  fill: none;
  stroke: rgba(0, 0, 0, 0.4);
  stroke-width: 2.5;
}

.circle-progress {
  fill: none;
  stroke: var(--rating-color);
  stroke-width: 3;
  stroke-linecap: round;
  transition: stroke-dasharray 0.6s ease;
}

.rating-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-family: 'Inter', 'Poppins', 'Nunito', sans-serif;
  font-weight: 700;
  color: white;
  font-size: 14px;
  line-height: 1;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
}

.rating-number {
  font-size: 14px;
}

.rating-symbol {
  font-size: 8px;
  vertical-align: super;
  opacity: 0.9;
}

/* Responsive */
@media (max-width: 768px) {
  .rating-badge {
    width: 40px;
    height: 40px;
  }

  .rating-text {
    font-size: 12px;
  }

  .rating-number {
    font-size: 12px;
  }

  .rating-symbol {
    font-size: 7px;
  }
}

@media (max-width: 480px) {
  .rating-badge {
    width: 36px;
    height: 36px;
  }

  .rating-text {
    font-size: 11px;
  }

  .rating-number {
    font-size: 11px;
  }

  .rating-symbol {
    font-size: 6px;
  }
}
</style>
