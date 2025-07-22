import { ref } from 'vue';

interface DockerImageInfo {
    version: string;
    lastUpdated: string;
    digest: string;
}

interface DockerManifest {
    mediaType: string;
    schemaVersion: number;
    config: {
        digest: string;
        mediaType: string;
        size: number;
    };
    layers: Array<{
        digest: string;
        mediaType: string;
        size: number;
    }>;
}

interface DockerConfig {
    created: string;
    config: {
        Labels?: {
            [key: string]: string;
        };
    };
}

export function useDockerRegistry() {
    const imageInfo = ref<DockerImageInfo | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function fetchImageInfo(registry: string, repository: string, tag: string = 'latest'): Promise<DockerImageInfo | null> {
        loading.value = true;
        error.value = null;

        try {
            // First, get the manifest
            const manifestUrl = `https://${registry}/v2/${repository}/manifests/${tag}`;

            const manifestResponse = await fetch(manifestUrl, {
                headers: {
                    Accept: 'application/vnd.docker.distribution.manifest.v2+json',
                },
                mode: 'cors',
            });

            if (!manifestResponse.ok) {
                if (manifestResponse.status === 401 || manifestResponse.status === 403) {
                    // Private repository or authentication required
                    console.warn('Docker registry authentication required or private repository');
                    return null;
                }
                throw new Error(`Failed to fetch manifest: ${manifestResponse.status}`);
            }

            const manifest: DockerManifest = await manifestResponse.json();

            // Get the config blob to extract creation date and labels
            const configUrl = `https://${registry}/v2/${repository}/blobs/${manifest.config.digest}`;

            const configResponse = await fetch(configUrl, {
                headers: {
                    Accept: 'application/vnd.docker.distribution.manifest.v2+json',
                },
                mode: 'cors',
            });

            if (!configResponse.ok) {
                throw new Error(`Failed to fetch config: ${configResponse.status}`);
            }

            const config: DockerConfig = await configResponse.json();

            // Extract version from labels or use tag as fallback
            let version = tag;
            if (config.config.Labels) {
                version =
                    config.config.Labels['org.opencontainers.image.version'] ||
                    config.config.Labels['version'] ||
                    config.config.Labels['org.label-schema.version'] ||
                    tag;
            }

            const imageData: DockerImageInfo = {
                version,
                lastUpdated: config.created,
                digest: manifest.config.digest.substring(7, 19), // First 12 chars of digest
            };

            imageInfo.value = imageData;
            return imageData;
        } catch (err) {
            // Silently fail as requested - just log to console and return null
            console.warn('Failed to fetch Docker image info (this is normal if CORS is blocked):', err);
            error.value = null; // Don't expose errors to UI
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function fetchGhcrImageInfo(owner: string, repository: string, tag: string = 'latest'): Promise<DockerImageInfo | null> {
        return fetchImageInfo('ghcr.io', `${owner}/${repository}`, tag);
    }

    function formatDate(dateString: string): string {
        try {
            const date = new Date(dateString);
            return date.toLocaleString('pt-BR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
            });
        } catch {
            return 'Data inválida';
        }
    }

    return {
        imageInfo,
        loading,
        error,
        fetchImageInfo,
        fetchGhcrImageInfo,
        formatDate,
    };
}
