<template>
  <section class="share-promo" aria-labelledby="match-share-promo-title">
    <div class="share-promo-body">
      <div class="share-promo-text">
        <h2 id="match-share-promo-title" class="share-promo-title">
          {{ t('summary.share.title') }}
        </h2>
        <p class="share-promo-subtitle">{{ t('summary.share.hint') }}</p>
        <InputText :model-value="shareUrl" readonly class="share-promo-url" />
        <div class="share-promo-actions">
          <Button
            v-if="canNativeShare"
            size="small"
            :label="t('summary.share.native')"
            icon="pi pi-share-alt"
            @click="shareLink"
          />
          <Button
            size="small"
            :label="linkCopied ? t('summary.share.copied') : t('summary.share.copy')"
            icon="pi pi-copy"
            severity="secondary"
            outlined
            @click="copyLink"
          />
        </div>
      </div>
      <a
        v-if="qrDataUrl"
        class="share-promo-qr-link"
        :href="shareUrl"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="t('summary.share.qrAria')"
      >
        <img
          class="share-promo-qr"
          :src="qrDataUrl"
          width="96"
          height="96"
          alt=""
          aria-hidden="true"
        />
        <span class="share-promo-qr-caption">{{ t('summary.share.qrCaption') }}</span>
      </a>
    </div>
  </section>
</template>

<script setup lang="ts">
import QRCode from 'qrcode'
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import { buildSharedMatchUrl } from '../../utils/buildSharedMatchUrl'

const props = defineProps<{
  shareUuid: string
  shareUrl?: string | null
}>()

const { t } = useI18n()

const shareUrl = computed(
  () => props.shareUrl?.trim() || buildSharedMatchUrl(props.shareUuid),
)
const qrDataUrl = ref<string | null>(null)
const linkCopied = ref(false)
const canNativeShare = computed(
  () => typeof navigator !== 'undefined' && typeof navigator.share === 'function',
)

watch(
  shareUrl,
  async (url) => {
    try {
      qrDataUrl.value = await QRCode.toDataURL(url, {
        width: 96,
        margin: 1,
        color: {
          dark: '#0f172a',
          light: '#ffffff',
        },
      })
    } catch {
      qrDataUrl.value = null
    }
  },
  { immediate: true },
)

async function copyLink(): Promise<void> {
  try {
    await navigator.clipboard.writeText(shareUrl.value)
    linkCopied.value = true
  } catch {
    linkCopied.value = false
  }
}

async function shareLink(): Promise<void> {
  if (!canNativeShare.value) return
  try {
    await navigator.share({
      title: t('summary.share.title'),
      url: shareUrl.value,
    })
  } catch {
    // User cancelled or share failed.
  }
}
</script>

<style scoped>
.share-promo {
  padding: var(--app-space-md);
  border-radius: var(--app-radius-lg);
  border: 1px solid #bbf7d0;
  background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 100%);
}

.share-promo-body {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--app-space-md);
}

.share-promo-text {
  min-width: 0;
  display: grid;
  gap: 0.5rem;
}

.share-promo-title {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 800;
  letter-spacing: -0.01em;
  color: #166534;
}

.share-promo-subtitle {
  margin: 0;
  font-size: 0.8125rem;
  line-height: 1.45;
  color: #475569;
}

.share-promo-url {
  width: 100%;
  font-size: 0.75rem;
}

.share-promo-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.share-promo-qr-link {
  flex-shrink: 0;
  display: grid;
  justify-items: center;
  gap: 0.25rem;
  padding: 0.375rem;
  border-radius: var(--app-radius);
  background: #ffffff;
  border: 1px solid var(--app-border);
  text-decoration: none;
}

.share-promo-qr {
  display: block;
  width: 96px;
  height: 96px;
}

.share-promo-qr-caption {
  font-size: 0.625rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: var(--app-text-muted);
}

@media (max-width: 360px) {
  .share-promo-body {
    flex-direction: column;
    align-items: stretch;
  }

  .share-promo-qr-link {
    justify-self: center;
  }
}
</style>
