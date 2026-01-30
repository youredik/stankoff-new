"use client";

import Head from "next/head";
import React from "react";
import {Box, Button, Typography} from "@mui/material";
import {signIn, useSession} from "next-auth/react";
import SyncLoader from "react-spinners/SyncLoader";
import {fetchHydra, HydraAdmin, hydraDataProvider, ResourceGuesser,} from "@api-platform/admin";
import type { ApiPlatformAdminDataProvider, ApiPlatformAdminGetListParams } from "@api-platform/admin";
import type { GetListResult } from "react-admin";
import {parseHydraDocumentation} from "@api-platform/api-doc-parser";

import {type Session} from "../../app/auth";
import authProvider from "../../components/admin/authProvider";
import Layout from "./layout/Layout";
import {ENTRYPOINT} from "../../config/entrypoint";
import i18nProvider from "./i18nProvider";
import supportTicketResourceProps from "../supportticket";
import {lightTheme} from "./theme";

const toDateOnly = (value: unknown): string | null => {
  if (!value) {
    return null;
  }

  if (value instanceof Date) {
    return value.toISOString().slice(0, 10);
  }

  if (typeof value === "string") {
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
      return value;
    }

    const parsed = new Date(value);
    if (!Number.isNaN(parsed.getTime())) {
      return parsed.toISOString().slice(0, 10);
    }
  }

  return null;
};

const addDays = (dateOnly: string, days: number): string | null => {
  const base = new Date(`${dateOnly}T00:00:00Z`);
  if (Number.isNaN(base.getTime())) {
    return null;
  }

  base.setUTCDate(base.getUTCDate() + days);
  return base.toISOString().slice(0, 10);
};

const applyDateFilter = (filter: Record<string, unknown>, field: string) => {
  const hasRange = Object.keys(filter).some((key) => key.startsWith(`${field}[`));
  if (hasRange) {
    return filter;
  }

  const dateOnly = toDateOnly(filter[field]);
  if (!dateOnly) {
    return filter;
  }

  const nextDay = addDays(dateOnly, 1);
  if (!nextDay) {
    return filter;
  }

  const updated = {...filter};
  delete updated[field];
  updated[`${field}[after]`] = dateOnly;
  updated[`${field}[strictly_before]`] = nextDay;

  return updated;
};

const normalizeDateFilters = (filter: Record<string, unknown> | undefined) => {
  if (!filter) {
    return filter;
  }

  let updated = {...filter};
  updated = applyDateFilter(updated, "createdAt");
  updated = applyDateFilter(updated, "closedAt");

  return updated;
};

const apiDocumentationParser = (session: Session) => async () => {
  try {
    return await parseHydraDocumentation(ENTRYPOINT, {
      headers: {
        Authorization: `Bearer ${session?.accessToken}`,
      },
    });
  } catch (result) {
    // @ts-ignore
    const {api, response, status} = result;
    if (status !== 401 || !response) {
      throw result;
    }

    return {
      api,
      response,
      status,
    };
  }
};

const AdminWithDataProvider = ({session, children,}: {
  session: Session;
  children?: React.ReactNode | undefined;
}) => {
  const baseProvider = hydraDataProvider({
    entrypoint: ENTRYPOINT,
    httpClient: (url: URL, options = {}) =>
      fetchHydra(url, {
        ...options,
        headers: {
          Authorization: `Bearer ${session?.accessToken}`,
        },
      }),
    apiDocumentationParser: apiDocumentationParser(session),
  }) as ApiPlatformAdminDataProvider;
  const dataProvider: ApiPlatformAdminDataProvider = {
    ...baseProvider,
    getList: (resource: string, params: ApiPlatformAdminGetListParams): Promise<GetListResult> => {
      const filter = normalizeDateFilters(params.filter);
      return baseProvider.getList(resource, {...params, filter});
    },
  };

  return (
    <HydraAdmin
      requireAuth
      authProvider={authProvider}
      dataProvider={dataProvider}
      entrypoint={window.origin}
      i18nProvider={i18nProvider}
      layout={Layout}
      theme={lightTheme}
    >
      {!!children && children}
    </HydraAdmin>
  );
};

const AdminWithDataProviderAndResources = ({session}: { session: Session }) => (
  <AdminWithDataProvider session={session}>
    <ResourceGuesser name="support_tickets" {...supportTicketResourceProps} />
  </AdminWithDataProvider>
);

const AdminWithOIDC = () => {
  // Can't use next-auth/middleware because of https://github.com/nextauthjs/next-auth/discussions/7488
  const {data: session, status} = useSession();
  const [isRedirecting, setIsRedirecting] = React.useState(false);

  if (status === "loading") {
    return <SyncLoader size={8} color="#46B6BF"/>;
  }

  // @ts-ignore
  if (!session || session?.error === "RefreshAccessTokenError") {
    if (!isRedirecting) {
      setIsRedirecting(true);
      void signIn("keycloak");
    }

    return (
      <Box sx={{textAlign: 'center', mt: 6}}>
        <Typography variant="h6" sx={{mb: 1}}>
          Сессия истекла
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{mb: 2}}>
          Перенаправляем на страницу входа…
        </Typography>
        <Button variant="contained" onClick={() => signIn("keycloak")}>
          Войти снова
        </Button>
      </Box>
    );
  }

  // @ts-ignore
  return <AdminWithDataProviderAndResources session={session}/>;
};

const Admin = () => (
  <>
    <Head>
      <title>API Platform Admin</title>
    </Head>

    {/*@ts-ignore*/}
    <AdminWithOIDC/>
  </>
);

export default Admin;
