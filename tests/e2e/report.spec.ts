import{test,expect}from'@playwright/test';test('unknown reports do not disclose data',async({request})=>{expect((await request.get('/healthcheck/report/unknown/')).status()).toBe(404);});
