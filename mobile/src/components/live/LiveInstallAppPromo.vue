<template>
  <section v-if="visible" class="install-promo" aria-labelledby="live-install-promo-title">
    <div class="install-promo-body">
      <div class="install-promo-text">
        <h2 id="live-install-promo-title" class="install-promo-title">
          {{ t('live.view.installApp.title') }}
        </h2>
        <p class="install-promo-subtitle">{{ t('live.view.installApp.subtitle') }}</p>
        <a
          class="install-promo-link"
          :href="storeUrl!"
          target="_blank"
          rel="noopener noreferrer"
        >
          {{ t('live.view.installApp.action') }}
        </a>
      </div>
      <a
        v-if="qrDataUrl"
        class="install-promo-qr-link"
        :href="storeUrl!"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="t('live.view.installApp.qrAria')"
      >
        <img
          class="install-promo-qr"
          :src="qrDataUrl"
          width="96"
          height="96"
          alt=""
          aria-hidden="true"
        />
        <span class="install-promo-qr-caption">{{ t('live.view.installApp.qrCaption') }}</span>
      </a>
    </div>
  </section>
</template>

<script setup lang="ts">
import { Capacitor } from '@capacitor/core'
import QRCode from 'qrcode'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { appConfigService } from '../../services/appConfig'

const { t } = useI18n()

const storeUrl = ref<string | null>(null)
const qrDataUrl = ref<string | null>(null)

const visible = computed(
  () => !Capacitor.isNativePlatform() && storeUrl.value !== null && qrDataUrl.value !== null,
)

onMounted(async () => {
  if (Capacitor.isNativePlatform()) {
    return
  }

  try {
    const config = await appConfigService.getVersionConfig()
    if (!config.androidStoreUrl) {
      return
    }

    storeUrl.value = config.androidStoreUrl
    qrDataUrl.value = await QRCode.toDataURL(config.androidStoreUrl, {
      width: 96,
      margin: 1,
      color: {
        dark: '#0f172a',
        light: '#ffffff',
      },
    })
  } catch {
    storeUrl.value = null
    qrDataUrl.value = null
  }
})
</script>

<style scoped>
.install-promo {
  padding: var(--app-space-md);
  border-radius: var(--app-radius-lg);
  border: 1px solid #bfdbfe;
  background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
}

.install-promo-body {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--app-space-md);
}

.install-promo-text {
  min-width: 0;
  display: grid;
  gap: 0.375rem;
}

.install-promo-title {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 800;
  letter-spacing: -0.01em;
  color: #1e3a8a;
}

.install-promo-subtitle {
  margin: 0;
  font-size: 0.8125rem;
  line-height: 1.45;
  color: #475569;
}

.install-promo-link {
  display: inline-flex;
  align-items: center;
  margin-top: 0.25rem;
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--app-primary-dark, #1f6b58);
  text-decoration: underline;
  text-underline-offset: 0.15em;
}

.install-promo-link:hover {
  color: var(--app-primary, #1f6b58);
}

.install-promo-qr-link {
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

.install-promo-qr {
  display: block;
  width: 96px;
  height: 96px;
}

.install-promo-qr-caption {
  font-size: 0.625rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: var(--app-text-muted);
}

@media (max-width: 360px) {
  .install-promo-body {
    flex-direction: column;
    align-items: stretch;
  }

  .install-promo-qr-link {
    justify-self: center;
  }
}
</style>
