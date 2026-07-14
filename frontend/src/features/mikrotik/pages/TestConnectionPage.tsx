import { ConnectionTester } from '../components/ConnectionTester';

export const TestConnectionPage = () => (
  <div className="mx-auto max-w-3xl space-y-6"><div><h1 className="text-2xl font-bold tracking-tight text-slate-900">Test MikroTik Connection</h1><p className="mt-1 text-sm text-slate-500">Verify TLS RouterOS API access before saving a router. Credentials are used only for this test and are not stored.</p></div><section className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><ConnectionTester /></section><aside className="rounded-xl border border-indigo-100 bg-indigo-50 p-5 text-sm text-indigo-900"><p className="font-semibold">Router prerequisite</p><p className="mt-1 leading-6">Use a dedicated least-privilege API user, enable <code className="rounded bg-white px-1">api-ssl</code>, restrict firewall access to SkyFi application servers, and trust the router certificate authority.</p></aside></div>
);
