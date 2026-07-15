# syntax=docker/dockerfile:1.7

FROM node:22-bookworm-slim AS dependencies
WORKDIR /app
COPY frontend/package*.json ./
RUN npm ci

FROM dependencies AS development
ENV HOST=0.0.0.0
COPY frontend ./
EXPOSE 5173
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]

FROM dependencies AS build
ARG VITE_API_BASE_URL=/api/v1
ENV VITE_API_BASE_URL=${VITE_API_BASE_URL}
COPY frontend ./
RUN npm run build

FROM nginx:1.27-alpine AS static
COPY --from=build /app/dist /usr/share/nginx/html
