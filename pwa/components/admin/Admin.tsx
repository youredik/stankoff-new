"use client";

import Head from "next/head";
import React from "react";
import {signIn, useSession} from "next-auth/react";
import SyncLoader from "react-spinners/SyncLoader";
import {fetchHydra, HydraAdmin, hydraDataProvider, ResourceGuesser,} from "@api-platform/admin";
import {parseHydraDocumentation} from "@api-platform/api-doc-parser";

import {type Session} from "../../app/auth";
import authProvider from "../../components/admin/authProvider";
import Layout from "./layout/Layout";
import {ENTRYPOINT} from "../../config/entrypoint";
import i18nProvider from "./i18nProvider";
import supportTicketResourceProps from "../supportticket";

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
  const dataProvider = hydraDataProvider({
    entrypoint: ENTRYPOINT,
    httpClient: (url: URL, options = {}) =>
      fetchHydra(url, {
        ...options,
        headers: {
          Authorization: `Bearer ${session?.accessToken}`,
        },
      }),
    apiDocumentationParser: apiDocumentationParser(session),
  });

  return (
    <HydraAdmin
      requireAuth
      authProvider={authProvider}
      dataProvider={dataProvider}
      entrypoint={window.origin}
      i18nProvider={i18nProvider}
      layout={Layout}
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

  if (status === "loading") {
    return <SyncLoader size={8} color="#46B6BF"/>;
  }

  // @ts-ignore
  if (!session || session?.error === "RefreshAccessTokenError") {
    (async () => await signIn("keycloak"))();

    return <SyncLoader size={8} color="#46B6BF"/>;
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
