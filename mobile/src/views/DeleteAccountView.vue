<template>
  <LegalDocument :title="t('legal.delete.title')" :updated-at="t('legal.updatedAt')">
    <p>{{ t('legal.delete.intro') }}</p>

    <h2>{{ t('legal.delete.s1.title') }}</h2>
    <ol>
      <li v-for="(item, index) in tm('legal.delete.s1.steps')" :key="index">{{ item }}</li>
    </ol>
    <p v-if="!auth.isAuthenticated" class="login-hint">
      {{ t('legal.delete.needLogin') }}
      <router-link :to="{ name: 'login', query: { redirect: '/delete-account' } }">
        {{ t('legal.delete.loginLink') }}
      </router-link>
    </p>

    <h2>{{ t('legal.delete.s2.title') }}</h2>
    <ul>
      <li v-for="(item, index) in tm('legal.delete.s2.items')" :key="index">{{ item }}</li>
    </ul>

    <h2>{{ t('legal.delete.s3.title') }}</h2>
    <p>{{ t('legal.delete.s3.p1') }}</p>
    <p>{{ t('legal.delete.s3.p2') }}</p>
  </LegalDocument>

  <DeleteAccountSection v-if="auth.isAuthenticated" />
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import LegalDocument from '../components/legal/LegalDocument.vue'
import DeleteAccountSection from '../components/legal/DeleteAccountSection.vue'
import { useAuthStore } from '../stores/auth'

const { t, tm } = useI18n()
const auth = useAuthStore()
</script>

<style scoped>
.login-hint {
  margin: 0;
}
</style>
