"use client";

import {signIn, signOut, useSession} from "next-auth/react";
import {usePathname} from "next/navigation";
import PersonOutlineIcon from "@mui/icons-material/PersonOutline";

import {NEXT_PUBLIC_OIDC_SERVER_URL} from "../../config/keycloak";

export const Header = () => {
  const pathname = usePathname();
  const { data: session, status } = useSession();

  if (pathname === "/" || pathname.match(/^\/admin/)) return <></>;

  return (
    <header className="bg-neutral-100 sticky top-0 z-10">
      <nav className="container mx-auto flex max-w-7xl items-center justify-between p-6 lg:px-8" aria-label="Global">
        <div className="block text-4xl font-bold">
          API Platform
        </div>
        <div className="lg:flex lg:flex-1 lg:justify-end lg:gap-x-12">
          {/* @ts-ignore */}
          {status === "authenticated" && (
            <a href="#" className="font-semibold text-gray-900" role="menuitem" onClick={(e) => {
              e.preventDefault();
              signOut({
                // @ts-ignore
                callbackUrl: `${NEXT_PUBLIC_OIDC_SERVER_URL}/protocol/openid-connect/logout?id_token_hint=${session.idToken}&post_logout_redirect_uri=${window.location.origin}`,
              });
            }}>
              Sign out
            </a>
          ) || (
            <a href="#" className="font-semibold text-gray-900" role="menuitem" onClick={(e) => {
              e.preventDefault();
              signIn("keycloak");
            }}>
              <PersonOutlineIcon className="w-6 h-6 mr-1"/>
              Log in
            </a>
          )}
        </div>
      </nav>
    </header>
  )
}
