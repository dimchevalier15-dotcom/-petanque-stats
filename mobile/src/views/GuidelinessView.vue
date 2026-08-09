<template>
  <PageHeader :title="t('doc.title')" :back-to="{ name: 'home' }" />

  <section class="guidelines">
    <p class="intro">
      {{ t('doc.introduction') }}
    </p>

    <section class="block">
      <h2>{{ t('doc.why.title') }}</h2>
      <p>{{ t('doc.why.text') }}</p>
    </section>

    <section class="block">
      <h2>{{ t('doc.commonScale.title') }}</h2>
      <p>{{ t('doc.commonScale.text') }}</p>
    </section>

    <section class="block">
      <h2>{{ t('doc.scale.title') }}</h2>

      <div class="rating negative2">
        <h3>{{ t('doc.scale.minus2.title') }}</h3>
        <p>{{ t('doc.scale.minus2.description') }}</p>

        <ul>
          <li>{{ t('doc.scale.minus2.example1') }}</li>
          <li>{{ t('doc.scale.minus2.example2') }}</li>
          <li>{{ t('doc.scale.minus2.example3') }}</li>
        </ul>

        <p class="summary">
          {{ t('doc.scale.minus2.summary') }}
        </p>
      </div>

      <div class="rating negative1">
        <h3>{{ t('doc.scale.minus1.title') }}</h3>
        <p>{{ t('doc.scale.minus1.description') }}</p>

        <ul>
          <li>{{ t('doc.scale.minus1.example1') }}</li>
          <li>{{ t('doc.scale.minus1.example2') }}</li>
          <li>{{ t('doc.scale.minus1.example3') }}</li>
          <li>{{ t('doc.scale.minus1.example4') }}</li>
        </ul>

        <p class="summary">
          {{ t('doc.scale.minus1.summary') }}
        </p>
      </div>

      <div class="rating neutral">
        <h3>{{ t('doc.scale.zero.title') }}</h3>
        <p>{{ t('doc.scale.zero.description') }}</p>

        <ul>
          <li>{{ t('doc.scale.zero.example1') }}</li>
          <li>{{ t('doc.scale.zero.example2') }}</li>
          <li>{{ t('doc.scale.zero.example3') }}</li>
        </ul>

        <p class="summary">
          {{ t('doc.scale.zero.summary') }}
        </p>
      </div>

      <div class="rating positive1">
        <h3>{{ t('doc.scale.plus1.title') }}</h3>
        <p>{{ t('doc.scale.plus1.description') }}</p>

        <ul>
          <li>{{ t('doc.scale.plus1.example1') }}</li>
          <li>{{ t('doc.scale.plus1.example2') }}</li>
          <li>{{ t('doc.scale.plus1.example3') }}</li>
          <li>{{ t('doc.scale.plus1.example4') }}</li>
        </ul>

        <p class="summary">
          {{ t('doc.scale.plus1.summary') }}
        </p>
      </div>

      <div class="rating positive2">
        <h3>{{ t('doc.scale.plus2.title') }}</h3>
        <p>{{ t('doc.scale.plus2.description') }}</p>

        <ul>
          <li>{{ t('doc.scale.plus2.example1') }}</li>
          <li>{{ t('doc.scale.plus2.example2') }}</li>
          <li>{{ t('doc.scale.plus2.example3') }}</li>
          <li>{{ t('doc.scale.plus2.example4') }}</li>
        </ul>

        <p class="summary">
          {{ t('doc.scale.plus2.summary') }}
        </p>
      </div>
    </section>

    <section class="block">
      <h2>{{ t('doc.faq.title') }}</h2>

      <div class="question">
        <h3>{{ t('doc.faq.carreau.title') }}</h3>
        <p>{{ t('doc.faq.carreau.answer') }}</p>
      </div>

      <div class="question">
        <h3>{{ t('doc.faq.palet.title') }}</h3>
        <p>{{ t('doc.faq.palet.answer') }}</p>
      </div>

      <div class="question">
        <h3>{{ t('doc.faq.firstPoint.title') }}</h3>
        <p>{{ t('doc.faq.firstPoint.answer') }}</p>
      </div>

      <div class="question">
        <h3>{{ t('doc.faq.difficulty.title') }}</h3>
        <p>{{ t('doc.faq.difficulty.answer') }}</p>
      </div>

      <div class="question">
        <h3>{{ t('doc.faq.beautiful.title') }}</h3>
        <p>{{ t('doc.faq.beautiful.answer') }}</p>
      </div>
    </section>

    <section class="block">
      <h2>{{ t('doc.philosophy.title') }}</h2>

      <p>{{ t('doc.philosophy.text1') }}</p>
      <p>{{ t('doc.philosophy.text2') }}</p>

      <blockquote>
        {{ t('doc.philosophy.quote') }}
      </blockquote>
    </section>
  </section>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import PageHeader from '../components/layout/PageHeader.vue'

const { t } = useI18n()
</script>

<style scoped>
p {
  margin-top: 0;
  margin-bottom: 0;
}
.guidelines {
  display: flex;
  flex-direction: column;
  gap: var(--app-space-lg);
}

.intro {
  line-height: 1.6;
  color: var(--app-text-muted);
}

.block {
  display: flex;
  flex-direction: column;
  gap: var(--app-space-md);
}

.block h2 {
  margin: 0;
  font-size: 1.0625rem;
}

.rating {
  border-left: 4px solid var(--app-primary);
  padding-left: var(--app-space-md);
}

.rating ul {
  margin: 0.5rem 0;
  padding-left: 1.2rem;
}

.summary {
  font-weight: 600;
}

.question {
  margin-bottom: var(--app-space-lg);
}

blockquote {
  margin: 0;
  padding-left: var(--app-space-md);
  border-left: 4px solid var(--app-accent);
  font-style: italic;
  color: var(--app-text-muted);
}
</style>