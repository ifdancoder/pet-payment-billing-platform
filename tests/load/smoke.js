import http from 'k6/http';
import { check, sleep } from 'k6';

const baseUrl = __ENV.BASE_URL || 'http://localhost:8080';

export const options = {
  scenarios: {
    tenant_reads: {
      executor: 'ramping-vus',
      stages: [
        { duration: '10s', target: 10 },
        { duration: '30s', target: 25 },
        { duration: '10s', target: 0 },
      ],
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<500', 'p(99)<1000'],
  },
};

export function setup() {
  const suffix = `${Date.now()}-${Math.random()}`;
  const response = http.post(`${baseUrl}/v1/auth/register`, JSON.stringify({
    email: `load-${suffix}@example.com`,
    password: 'correct horse battery staple',
    merchant_name: 'Load test merchant',
  }), { headers: { 'Content-Type': 'application/json' } });

  if (!check(response, { 'account bootstrap succeeds': (r) => r.status === 201 })) {
    throw new Error(`bootstrap failed: ${response.status} ${response.body}`);
  }
  const account = response.json();
  return { merchantId: account.merchant_id, accessToken: account.access_token };
}

export default function (account) {
  const response = http.get(`${baseUrl}/v1/merchants/${account.merchantId}/customers`, {
    headers: {
      Authorization: `Bearer ${account.accessToken}`,
      'X-Correlation-ID': '11111111-1111-4111-8111-111111111111',
    },
  });
  check(response, {
    'tenant read is authorized': (r) => r.status === 200,
    'correlation id is preserved': (r) => r.headers['X-Correlation-Id'] === '11111111-1111-4111-8111-111111111111',
  });
  sleep(0.2);
}
