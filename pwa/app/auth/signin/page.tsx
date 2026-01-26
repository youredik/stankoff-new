"use client";

import { signIn } from "next-auth/react";
import { useEffect } from "react";
import { useSearchParams } from "next/navigation";
import SyncLoader from "react-spinners/SyncLoader";

export default function SignIn() {
  const searchParams = useSearchParams();
  const callbackUrl = searchParams.get('callbackUrl') || '/admin';

  useEffect(() => {
    signIn("keycloak", { callbackUrl });
  }, [callbackUrl]);

  return (
    <div className="flex items-center justify-center min-h-screen">
      <SyncLoader size={8} color="#46B6BF" />
    </div>
  );
}
