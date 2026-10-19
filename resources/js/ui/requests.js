/** One request stream per list. Stale responses never update the UI or clear a newer busy state. */
export function createLatestRequest(fetcher = (...args) => fetch(...args)) {
    let controller, version = 0;
    return async (url, { onSuccess, onError, onFinally, json = false } = {}) => {
        controller?.abort();
        controller = new AbortController();
        const current = ++version;
        try {
            const response = await fetcher(url, {
                signal: controller.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': json ? 'application/json' : 'text/html' },
            });
            if (!response.ok || response.redirected) throw new Error('Daftar gagal dimuat. Periksa koneksi atau muat ulang halaman.');
            const payload = json ? await response.json() : await response.text();
            if (current === version) await onSuccess?.(payload);
        } catch (error) {
            if (error.name !== 'AbortError' && current === version) {
                if (onError) onError(error);
                else window.showUiMessage?.(error.message);
            }
        } finally {
            if (current === version) onFinally?.();
        }
    };
}
