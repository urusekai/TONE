<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';

/** 기본 선택값 */
const selectedPlan = ref('basic');
</script>

<template>
  <main id="membership">
    <!-- 이용권 선택 -->
    <section class="membership-plans">
      <h2 class="section-title">이용권 선택</h2>

      <!-- 베이직 -->
      <div class="plan-option">
        <input
          id="plan-basic"
          class="plan-radio"
          type="radio"
          name="membershipPlan"
          value="basic"
          v-model="selectedPlan"
        />
        <label class="plan-card" for="plan-basic">
          <div class="plan-badge">베이직</div>

          <div class="price-row">
            <span class="price-main">3,900원</span>
            <span class="price-period">(매월)</span>
          </div>

          <ul class="benefit-list">
            <li>광고 제거</li>
            <li>자유로운 재생 / 스킵</li>
            <li class="disabled">더 향상된 음질</li>
            <li class="disabled">오프라인 재생</li>
          </ul>
        </label>
      </div>

      <!-- 프로 -->
      <div class="plan-option">
        <input
          id="plan-pro"
          class="plan-radio"
          type="radio"
          name="membershipPlan"
          value="pro"
          v-model="selectedPlan"
        />
        <label class="plan-card" for="plan-pro">
          <div class="plan-badge">프로</div>

          <div class="price-row">
            <span class="price-main">7,900원</span>
            <span class="price-period">(매월)</span>
          </div>

          <ul class="benefit-list">
            <li>광고 제거</li>
            <li>자유로운 재생 / 스킵</li>
            <li>더 향상된 음질</li>
            <li>오프라인 재생</li>
          </ul>
        </label>
      </div>
    </section>

    <!-- 유의사항 -->
    <section class="policy-section">
      <h2 class="section-title">멤버십 유의사항</h2>
      <ul class="policy-list">
        <li>본 상품은 매월 자동으로 결제되는 정기구독 상품입니다.</li>
        <li>언제든지 해지할 수 있으며, 해지 시 다음 결제일로부터 요금이 청구되지 않습니다.</li>
        <li>해지 전까지는 결제된 이용기간 동안 서비스 이용이 가능합니다.</li>
        <li>환불은 서비스 이용 여부 및 환불 정책에 따라 처리됩니다.</li>
        <li>무료 체험 또는 프로모션 적용 시 종료 후 정상 요금이 자동 결제됩니다.</li>
      </ul>
    </section>

    <!-- 결제 버튼 -->
    <section class="payment-section">
      <RouterLink class="btn-primary" :to="{ path: '/payment', query: { plan: selectedPlan } }"
        >결제하기</RouterLink
      >
    </section>
  </main>
</template>

<style scoped>
/* ===========================
   MEMBERSHIP PAGE
=========================== */

#membership {
  width: 100%;
  justify-content: flex-start;
  align-items: stretch;
  gap: 50px;
  padding-top: calc(var(--app-main-top) + 16px);
  padding-bottom: calc(var(--app-main-bottom) + 16px);
  box-sizing: border-box;

  margin: 0 auto;
}

#membership .membership-plans,
#membership .policy-section,
#membership .payment-section {
  width: 100%;
  max-width: 420px;
}

/* 타이틀 */
#membership .section-title {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 12px;
  color: #3f5f73;
}

/* ===========================
   PLAN CARDS
=========================== */

.plan-option {
  margin-bottom: 18px;
}

/* 라디오 숨기기 (label 클릭으로 선택) */
.plan-radio {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.plan-card {
  display: block;
  background: #ffffff;
  border-radius: 18px;
  padding: 20px 18px;
  border: 2px solid #e3e8ec;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.06);
  transition: all 0.2s ease-in-out;
  cursor: pointer;
}

/* 선택된 카드(프로 선택 시 테두리 강조) */
.plan-radio:checked + .plan-card {
  border-color: #3f5f73;
  box-shadow: 0 0 4px rgba(0, 0, 0, 0.25);
}

.plan-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 34px;
  padding: 0 15px;
  border-radius: 20px;
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

/* 혜택 */
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

/* ===========================
   POLICY
=========================== */

#membership .policy-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

#membership .policy-list li {
  position: relative;
  padding-left: 14px;
  margin-bottom: 5px;
  font-size: 14px;
  color: #4c6a7a;
  line-height: 1.5;
}

#membership .policy-list li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0.7em;
  width: 4px;
  height: 4px;
  transform: translateY(-50%);
  background: url(../assets/icons/bullet.svg) no-repeat center / contain;
}

#membership .policy-list li:last-child {
  margin-bottom: 0;
}

/* ===========================
   BUTTON
=========================== */

.payment-section {
  padding-top: 10px;
  padding-bottom: calc(10px + env(safe-area-inset-bottom));
}

#membership .btn-primary {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 54px;
  border-radius: 14px;
  border: none;
  background: #3f5f73;
  color: #ffffff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
}
</style>
