import { ref, onMounted, onUnmounted } from 'vue'

export function useInfiniteScroll(loadMoreCallback) {
  const observer = ref(null)
  const loadMoreTrigger = ref(null)
  const isLoading = ref(false)
  const hasMore = ref(true)

  const observerCallback = (entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && !isLoading.value && hasMore.value) {
        loadMoreCallback()
      }
    })
  }

  const setupObserver = () => {
    observer.value = new IntersectionObserver(observerCallback, {
      root: null,
      rootMargin: '100px',
      threshold: 0.1
    })

    if (loadMoreTrigger.value) {
      observer.value.observe(loadMoreTrigger.value)
    }
  }

  const disconnectObserver = () => {
    if (observer.value) {
      observer.value.disconnect()
    }
  }

  onMounted(() => {
    setTimeout(setupObserver, 100)
  })

  onUnmounted(() => {
    disconnectObserver()
  })

  return {
    loadMoreTrigger,
    isLoading,
    hasMore,
    setupObserver,
    disconnectObserver
  }
}
