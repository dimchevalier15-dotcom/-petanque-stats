# Mobile (frontend) Dockerfile
FROM node:20-alpine

WORKDIR /app

COPY package.json package-lock.json* pnpm-lock.yaml* yarn.lock* .npmrc* ./
RUN corepack enable && (pnpm --version >/dev/null 2>&1 && pnpm install || npm ci || npm install)

COPY . .

EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
