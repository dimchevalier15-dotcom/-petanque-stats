<template>
  <section class="guidelines">
    <h1>{{ t('doc.title') }}</h1>

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

const { t } = useI18n()
</script>

<style scoped>
p {
  margin-top: 0;
  margin-bottom: 0;
}
.guidelines {
  max-width: 800px;
  margin: 0 auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.intro {
  line-height: 1.6;
}

.block {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.rating {
  border-left: 4px solid var(--primary-color);
  padding-left: 1rem;
}

.rating h3 {
  margin-bottom: .5rem;
}

.rating ul {
  margin: .5rem 0;
  padding-left: 1.2rem;
}

.summary {
  font-weight: 600;
}

.question {
  margin-bottom: 1.5rem;
}

blockquote {
  margin-top: 1rem;
  padding-left: 1rem;
  border-left: 4px solid var(--primary-color);
  font-style: italic;
  opacity: .9;
}
</style>