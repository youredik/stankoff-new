import { auth } from "./app/auth";

export default auth((req) => {
  const isAuth = !!req.auth;
  const isAuthPage = req.nextUrl.pathname.startsWith('/api/auth') || req.nextUrl.pathname.startsWith('/auth');
  const isPublicPage = req.nextUrl.pathname === '/';
  const isGuestPage = req.nextUrl.pathname.startsWith('/guest');

  if (!isAuth && !isAuthPage && !isPublicPage && !isGuestPage) {
    const newUrl = new URL("/auth/signin", req.nextUrl.origin);
    newUrl.searchParams.set('callbackUrl', `${req.nextUrl.pathname}${req.nextUrl.search}`);
    return Response.redirect(newUrl);
  }
});

export const config = {
  matcher: ["/((?!api|_next/static|_next/image|favicon.ico).*)"],
};
