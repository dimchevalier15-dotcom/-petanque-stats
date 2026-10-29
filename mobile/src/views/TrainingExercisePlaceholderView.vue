<template>
  <AppPage>
    <PageHeader :title="title" :subtitle="subtitle" :back-to="{ name: 'trainingHome' }" />
    <EmptyState :title="t('training.menu.comingSoon')" icon="pi pi-wrench" />
  </AppPage>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import AppPage from '../components/layout/AppPage.vue'
import EmptyState from '../components/layout/EmptyState.vue'
import PageHeader from '../components/layout/PageHeader.vue'

const { t } = useI18n()
const route = useRoute()

const exerciseKey = computed(() => String(route.meta.trainingExercise ?? 'pointPrecision'))

const title = computed(() => t(`training.menu.${exerciseKey.value}.title`))
const subtitle = computed(() => t(`training.menu.${exerciseKey.value}.description`))
</script>
