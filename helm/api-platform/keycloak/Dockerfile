#syntax=docker/dockerfile:1.4




# Versions
FROM docker.io/bitnamilegacy/keycloak:26-debian-12 AS keycloak_upstream


# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target


# Keycloak image
FROM keycloak_upstream AS keycloak

COPY --link themes/api-platform-stankoff /opt/bitnami/keycloak/themes/api-platform-stankoff
COPY --link providers/owner-policy.jar /opt/bitnami/keycloak/providers/owner-policy.jar
