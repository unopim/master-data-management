@php
    $graphHops = max(1, (int) config('masterdata.graph.max_hops', 2));

    $graphHopLabels = collect(range(1, $graphHops))->mapWithKeys(fn (int $hops): array => [$hops => trans_choice('masterdata::app.graph.hop-count', $hops, ['count' => $hops])]);
@endphp

<v-masterdata-record-graph
    src="{{ route('admin.masterdata.records.graph', $record->uuid) }}"
    :hop-labels='@json($graphHopLabels)'
></v-masterdata-record-graph>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-masterdata-record-graph-template"
    >
        <div>
            <div class="flex flex-wrap items-center justify-between gap-2.5 mb-3">
                <div>
                    <p class="text-base text-gray-800 dark:text-white font-semibold">
                        @lang('masterdata::app.graph.title')
                    </p>

                    <p class="text-xs text-gray-500 dark:text-gray-300">
                        @lang('masterdata::app.graph.info')
                    </p>
                </div>

                <div
                    class="flex items-center gap-2.5"
                    role="group"
                    aria-label="@lang('masterdata::app.graph.hops')"
                >
                    <button
                        v-for="option in hopOptions"
                        :key="option"
                        type="button"
                        :class="option === hops ? 'primary-button' : 'secondary-button'"
                        :aria-pressed="String(option === hops)"
                        @click="load(option)"
                        v-text="hopLabel(option)"
                    ></button>
                </div>
            </div>

            <p
                v-if="loading"
                class="py-2 text-sm text-gray-500 dark:text-gray-300"
            >
                @lang('masterdata::app.graph.loading')
            </p>

            <template v-else>
                <p
                    v-if="nodes.length <= 1"
                    class="py-2 text-sm text-gray-500 dark:text-gray-300"
                >
                    @lang('masterdata::app.graph.empty')
                </p>

                <template v-else>
                    <svg
                        class="w-full select-none"
                        :viewBox="`0 0 ${width} ${height}`"
                        role="img"
                        aria-labelledby="masterdata-graph-title masterdata-graph-desc"
                    >
                        <title id="masterdata-graph-title">@lang('masterdata::app.graph.title')</title>

                        <desc id="masterdata-graph-desc" v-text="description"></desc>

                        <g>
                            <line
                                v-for="(edge, index) in placedEdges"
                                :key="'edge-' + index"
                                :x1="edge.from.x"
                                :y1="edge.from.y"
                                :x2="edge.to.x"
                                :y2="edge.to.y"
                                stroke="currentColor"
                                stroke-width="1.5"
                                :stroke-dasharray="dash(edge.kind)"
                                :class="edgeClass(edge.kind)"
                            >
                                <title v-text="edge.label"></title>
                            </line>
                        </g>

                        <g
                            v-for="node in placed"
                            :key="node.id"
                            :transform="`translate(${node.x}, ${node.y})`"
                        >
                            <a
                                v-if="node.url"
                                :href="node.url"
                                :aria-label="nodeName(node)"
                                class="focus:outline-none"
                            >
                                <circle
                                    :r="node.hop === 0 ? 14 : 9"
                                    fill="currentColor"
                                    :class="nodeClass(node)"
                                ></circle>
                            </a>

                            <rect
                                v-else
                                x="-8"
                                y="-8"
                                width="16"
                                height="16"
                                rx="3"
                                fill="currentColor"
                                :class="nodeClass(node)"
                            >
                                <title v-text="nodeName(node)"></title>
                            </rect>

                            <text
                                y="26"
                                text-anchor="middle"
                                fill="currentColor"
                                class="text-xs text-gray-600 dark:text-gray-300"
                                aria-hidden="true"
                                v-text="node.code"
                            ></text>
                        </g>
                    </svg>

                    <div class="flex flex-wrap items-center gap-4 mt-2 text-xs text-gray-600 dark:text-gray-300">
                        <span class="flex items-center gap-1.5">
                            <svg width="24" height="6" aria-hidden="true"><line x1="0" y1="3" x2="24" y2="3" stroke="currentColor" stroke-width="2" class="text-primary-600 dark:text-primary-400"></line></svg>
                            @lang('masterdata::app.graph.legend.relation')
                        </span>

                        <span class="flex items-center gap-1.5">
                            <svg width="24" height="6" aria-hidden="true"><line x1="0" y1="3" x2="24" y2="3" stroke="currentColor" stroke-width="2" stroke-dasharray="6 3" class="text-gray-500 dark:text-gray-400"></line></svg>
                            @lang('masterdata::app.graph.legend.parent')
                        </span>

                        <span class="flex items-center gap-1.5">
                            <svg width="24" height="6" aria-hidden="true"><line x1="0" y1="3" x2="24" y2="3" stroke="currentColor" stroke-width="2" stroke-dasharray="2 3" class="text-success"></line></svg>
                            @lang('masterdata::app.graph.legend.source')
                        </span>
                    </div>

                    <p
                        v-if="truncated"
                        class="mt-2 text-xs text-gray-500 dark:text-gray-400"
                    >
                        @lang('masterdata::app.graph.truncated')
                    </p>

                    <ul class="sr-only">
                        <li
                            v-for="(edge, index) in placedEdges"
                            :key="'item-' + index"
                            v-text="nodeName(edge.from) + ' — ' + edge.label + ' — ' + nodeName(edge.to)"
                        ></li>
                    </ul>
                </template>
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-masterdata-record-graph', {
            template: '#v-masterdata-record-graph-template',

            props: {
                src: {
                    type: String,
                    required: true,
                },

                hopLabels: {
                    type: Object,
                    default: () => ({ 1: '1' }),
                },
            },

            data() {
                return {
                    width: 800,

                    height: 480,

                    hops: 1,

                    loading: true,

                    nodes: [],

                    edges: [],

                    placed: [],

                    truncated: false,
                };
            },

            computed: {
                hopOptions() {
                    return Object.keys(this.hopLabels).map(Number);
                },

                byId() {
                    return Object.fromEntries(this.placed.map((node) => [node.id, node]));
                },

                placedEdges() {
                    return this.edges
                        .filter((edge) => this.byId[edge.source] && this.byId[edge.target])
                        .map((edge) => ({ ...edge, from: this.byId[edge.source], to: this.byId[edge.target] }));
                },

                description() {
                    return "@lang('masterdata::app.graph.description')"
                        .replace(':nodes', this.nodes.length)
                        .replace(':edges', this.edges.length);
                },
            },

            mounted() {
                this.load(1);
            },

            methods: {
                load(hops) {
                    this.hops = hops;

                    this.loading = true;

                    this.$axios.get(this.src, { params: { hops } })
                        .then((response) => {
                            this.nodes = response.data.nodes;

                            this.edges = response.data.edges;

                            this.truncated = response.data.truncated;

                            this.placed = this.layout(this.nodes, this.edges);
                        })
                        .catch((error) => {
                            this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message });
                        })
                        .finally(() => {
                            this.loading = false;
                        });
                },

                layout(nodes, edges) {
                    const cx = this.width / 2;

                    const cy = this.height / 2;

                    const points = nodes.map((node, index) => {
                        const angle = (2 * Math.PI * index) / Math.max(1, nodes.length - 1);

                        const radius = node.hop === 0 ? 0 : 110 * node.hop;

                        return { ...node, x: cx + radius * Math.cos(angle), y: cy + radius * Math.sin(angle), vx: 0, vy: 0 };
                    });

                    const index = Object.fromEntries(points.map((point, position) => [point.id, position]));

                    const links = edges
                        .filter((edge) => index[edge.source] !== undefined && index[edge.target] !== undefined)
                        .map((edge) => [index[edge.source], index[edge.target]]);

                    for (let step = 0; step < 300; step++) {
                        const cooling = 1 - step / 300;

                        for (let i = 0; i < points.length; i++) {
                            for (let j = i + 1; j < points.length; j++) {
                                const dx = points[i].x - points[j].x || 0.01;

                                const dy = points[i].y - points[j].y || 0.01;

                                const distance = Math.max(1, dx * dx + dy * dy);

                                const force = 2400 / distance;

                                points[i].vx += dx * force / Math.sqrt(distance);
                                points[i].vy += dy * force / Math.sqrt(distance);
                                points[j].vx -= dx * force / Math.sqrt(distance);
                                points[j].vy -= dy * force / Math.sqrt(distance);
                            }
                        }

                        for (const [source, target] of links) {
                            const dx = points[target].x - points[source].x;

                            const dy = points[target].y - points[source].y;

                            const distance = Math.sqrt(dx * dx + dy * dy) || 1;

                            const pull = (distance - 100) * 0.02;

                            points[source].vx += dx / distance * pull;
                            points[source].vy += dy / distance * pull;
                            points[target].vx -= dx / distance * pull;
                            points[target].vy -= dy / distance * pull;
                        }

                        for (const point of points) {
                            if (point.hop === 0) {
                                point.x = cx;
                                point.y = cy;

                                continue;
                            }

                            point.x = Math.min(this.width - 30, Math.max(30, point.x + point.vx * cooling));
                            point.y = Math.min(this.height - 30, Math.max(30, point.y + point.vy * cooling));
                            point.vx *= 0.5;
                            point.vy *= 0.5;
                        }
                    }

                    return points;
                },

                hopLabel(hops) {
                    return this.hopLabels[hops] ?? String(hops);
                },

                nodeName(node) {
                    return [node.code, node.label, node.type, node.status].filter(Boolean).join(' · ');
                },

                nodeClass(node) {
                    if (node.hop === 0) {
                        return 'text-primary-600 dark:text-primary-400';
                    }

                    return node.kind === 'source' ? 'text-success' : 'text-gray-500 dark:text-gray-400';
                },

                edgeClass(kind) {
                    return {
                        relation: 'text-primary-600 dark:text-primary-400',
                        parent: 'text-gray-500 dark:text-gray-400',
                        source: 'text-success',
                    }[kind];
                },

                dash(kind) {
                    return { relation: null, parent: '6 3', source: '2 3' }[kind];
                },
            },
        });
    </script>
@endPushOnce
