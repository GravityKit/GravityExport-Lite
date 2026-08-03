import type { APIRequestContext } from '@playwright/test';

const HEADERS = { 'x-gk-probe': 'gk-e2e-probe' };

export interface ProbeRequest {
	url: string;
	body: { batch?: AnalyticsEvent[] } | null;
	at: number;
}

export interface AnalyticsEvent {
	event: string;
	properties: Record<string, unknown>;
	groups: Record<string, string>;
	timestamp: string;
}

export interface ProbeState {
	count: number;
	requests: ProbeRequest[];
	consent: Record<string, unknown> | false;
	salt_set: boolean;
}

/** Reads everything the plugin tried to transmit, plus the consent record. */
export async function readProbe( request: APIRequestContext ): Promise<ProbeState> {
	const response = await request.get( '/?rest_route=/gk-probe/v1/log', { headers: HEADERS } );

	if ( ! response.ok() ) {
		throw new Error( `Probe unreadable (${ response.status() }). Is the mu-plugin installed?` );
	}

	return response.json();
}

/** Clears the transmission log and the consent record. */
export async function resetProbe( request: APIRequestContext ): Promise<void> {
	const response = await request.post( '/?rest_route=/gk-probe/v1/reset', { headers: HEADERS } );

	if ( ! response.ok() ) {
		throw new Error( `Probe reset failed (${ response.status() }).` );
	}
}

/** Flattens every captured event across every recorded request. */
export function events( state: ProbeState ): AnalyticsEvent[] {
	return state.requests.flatMap( ( r ) => r.body?.batch ?? [] );
}
