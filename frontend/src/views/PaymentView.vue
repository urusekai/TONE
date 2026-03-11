<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { updateMyMembershipPlan } from '@/services/userService';
import { showAlert } from '@/utils/alert';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToastStore();
const isSubmitting = ref(false);

/** 멤버십에서 넘어온 plan (없거나 이상하면 pro 기본) */
const plan = computed(() => {
  const p = route.query.plan;
  return p === 'basic' || p === 'pro' ? p : 'basic';
});

/** 플랜별 표시 데이터 */
const planData = computed(() => {
  const map = {
    basic: {
      badge: '베이직',
      priceNumber: 3900,
      priceText: '3,900원',
      totalText: '3,900 / 월',
      buttonText: '3,900원 결제하기',
      benefits: [
        { text: '광고 제거', disabled: false },
        { text: '자유로운 재생 / 스킵', disabled: false },
        { text: '더 향상된 음질', disabled: true },
        { text: '오프라인 재생', disabled: true }
      ]
    },
    pro: {
      badge: '프로',
      priceNumber: 7900,
      priceText: '7,900원',
      totalText: '7,900 / 월',
      buttonText: '7,900원 결제하기',
      benefits: [
        { text: '광고 제거', disabled: false },
        { text: '자유로운 재생 / 스킵', disabled: false },
        { text: '더 향상된 음질', disabled: false },
        { text: '오프라인 재생', disabled: false }
      ]
    }
  };
  return map[plan.value];
});

async function handleSubmitMembership() {
  if (isSubmitting.value) return;

  isSubmitting.value = true;

  try {
    const result = await updateMyMembershipPlan(plan.value);

    if (result?.user) {
      authStore.setCurrentUser(result.user);
    }

    toast.show('이용권이 변경되었습니다');
    router.replace('/my-page');
  } catch (error) {
    const message = error instanceof Error ? error.message : '이용권 변경 중 오류가 발생했습니다.';
    showAlert(message);
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <main id="payment">
    <!-- 선택한 이용권 -->
    <section class="selected-plan-section">
      <h2 class="section-title">선택한 이용권</h2>

      <div class="plan-option">
        <input
          id="payment-plan"
          class="plan-radio"
          type="radio"
          name="paymentPlan"
          :value="plan"
          checked
          disabled
        />
        <label class="plan-card" for="payment-plan">
          <div class="plan-badge">{{ planData.badge }}</div>

          <div>
            <span class="price-main">{{ planData.priceText }}</span>
            <span class="price-period">(매월)</span>
          </div>

          <ul class="benefit-list">
            <li v-for="b in planData.benefits" :key="b.text" :class="{ disabled: b.disabled }">
              {{ b.text }}
            </li>
          </ul>
        </label>
      </div>
    </section>

    <!-- 결제 정보 -->
    <section class="info-section">
      <h2 class="section-title">결제 정보</h2>
      <div class="info-card">
        <div class="info-row">
          <span>결제 수단</span>
          <a class="clickable" href="javascript:void(0);">
            <span>신용 · 체크카드</span>
            <img class="row-arrow" src="@/assets/icons/arrow-right.svg" alt="" aria-hidden="true" />
          </a>
        </div>

        <div class="info-row">
          <span>다음 결제일</span>
          <span>2026.03.09</span>
        </div>
      </div>
    </section>

    <!-- 자동결제 안내 -->
    <section class="notice-section">
      <h2 class="section-title">자동결제 안내</h2>
      <ul class="policy-list">
        <li>본 이용권은 매월 자동으로 결제됩니다.</li>
        <li>언제든지 해지할 수 있으며, 해지 시 다음 결제일부터 요금이 청구되지 않습니다.</li>
        <li>해지 전까지는 결제된 이용기간 동안 서비스 이용이 가능합니다.</li>
        <li>환불은 서비스 이용 여부 및 정책에 따라 처리됩니다.</li>
        <li>프로모션 또는 할인 적용 시, 종료 후 정상 요금이 결제됩니다.</li>
      </ul>
    </section>

    <!-- 결제 금액 -->
    <section class="amount-section">
      <h2 class="section-title">결제 금액</h2>
      <div class="amount-card">
        <div class="info-row">
          <span>이용권 금액</span>
          <span>{{ planData.priceText }}</span>
        </div>

        <div class="info-row">
          <span>부가세(VAT)</span>
          <span>포함</span>
        </div>

        <div class="total-row">
          <span>총 결제 금액</span>
          <span class="total-price">{{ planData.totalText }}</span>
        </div>
      </div>
    </section>

    <!-- 결제 버튼 -->
    <section class="payment-action-section">
      <button type="button" class="btn-primary" :disabled="isSubmitting" @click="handleSubmitMembership">
        {{ isSubmitting ? '처리중...' : planData.buttonText }}
      </button>
    </section>
  </main>
</template>
<style scoped>
/* ===========================
   PAYMENT PAGE
=========================== */

#payment {
  display: flex;
  flex-direction: column;
  width: 100%;
  justify-content: flex-start;
  align-items: stretch;
  gap: 50px;
  padding-top: calc(var(--app-main-top) + 16px);
  padding-bottom: calc(var(--app-main-bottom) + 16px);
  box-sizing: border-box;
}

.selected-plan-section,
.info-section,
.notice-section,
.amount-section,
.payment-action-section {
  width: 100%;
  max-width: 420px;
  margin: 0 auto;
}

#payment .section-title {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 12px;
  color: #3f5f73;
}

/* ===========================
   PLAN CARDS
=========================== */

.plan-card {
  display: block;
  background: #ffffff;
  border-radius: 18px;
  padding: 20px 18px;
  border: 2px solid #3f5f73;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.06);
  transition: all 0.2s ease-in-out;
}

.plan-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 34px;
  padding: 0 15px;
  border-radius: 20px;
  border: 1px;
  background: #3f5f73;
  color: #ffffff;
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 14px;
}

.price-row {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.price-main {
  font-size: 30px;
  font-weight: 800;
  letter-spacing: 0px;
  color: #3f5f73;
}

.price-period {
  font-size: 14px;
  font-weight: 700;
  color: #3f5f73;
}

/* 라디오 숨기기 (label 클릭으로 선택) */
.plan-radio {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

/* 해택 */
.benefit-list {
  list-style: none;
  padding: 0;
  margin: 8px 0 0;
}

.benefit-list li {
  position: relative;
  padding-left: 10px;
  font-size: 14px;
  color: #4c6a7a;
  line-height: 1.5;
}

.benefit-list li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0.75em;
  width: 4px;
  height: 4px;
  transform: translateY(-50%);
  background: url(../assets/icons/bullet.svg) no-repeat center / contain;
}

.benefit-list li.disabled {
  opacity: 0.35;
}

#payment .policy-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

#payment .policy-list li {
  position: relative;
  padding-left: 14px;
  font-size: 14px;
  color: #4c6a7a;
  line-height: 1.5;
  margin-bottom: 5px;
}

#payment .policy-list li:last-child {
  margin-bottom: 0;
}

#payment .policy-list li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0.7em;
  width: 4px;
  height: 4px;
  transform: translateY(-50%);
  background: url(../assets/icons/bullet.svg) no-repeat center / contain;
}

.benefit-list li.disabled {
  opacity: 0.35;
}

#payment .info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  font-size: 14px;
  color: #3f5f73;
}

#payment .info-card .info-row,
#payment .amount-card .info-row {
  padding: 12px 0;
  border-bottom: 1px solid #d6dee3;
}

#payment .clickable {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: #3f5f73;
  font-weight: 400;
  text-decoration: none;
}

#payment .row-arrow {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

#payment .total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 12px;
}

#payment .total-row span:first-child {
  font-size: 16px;
  font-weight: 700;
}

#payment .total-price {
  font-size: 18px;
  font-weight: 700;
  color: #3f5f73;
}

#payment .btn-primary {
  width: 100%;
  height: 54px;
  border-radius: 14px;
  border: none;
  background: #3f5f73;
  color: #ffffff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}
</style>
