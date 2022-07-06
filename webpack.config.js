// Hint: if something doesn't work as expected: yarn upgrade --latest

const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build/')
    .setManifestKeyPrefix('build/')
    .cleanupOutputBeforeBuild()

    .addEntry('app', './assets/app.js')
    .addEntry('invoice', './assets/invoice.js')
    .addEntry('invoice-pdf', './assets/invoice-pdf.js')
    .addEntry('chart', './assets/chart.js')
    .addEntry('calendar', './assets/calendar.js')
    .addEntry('dashboard', './assets/dashboard.js')

    .splitEntryChunks()
    .configureSplitChunks(function(splitChunks) {
        splitChunks.chunks = 'async';
    })

    // in the past there was a bug with empty hashes in entrypoints.json, disable if it happens again
    .enableIntegrityHashes(Encore.isProduction())
    .enableSingleRuntimeChunk()
    .enableVersioning(Encore.isProduction())
    .enableSourceMaps(!Encore.isProduction())
    .enableBuildNotifications()

    .enableSassLoader(function(sassOptions) {}, {
        resolveUrlLoader: false
    })

    .configureCssMinimizerPlugin((options) => {
        options.minimizerOptions = {
            preset: ['default', { discardComments: { removeAll: true } }],
        }
    })

    // compress javascript in production build
    .configureTerserPlugin((options) => {
        options.terserOptions = {
            compress: true,
            output: {
                comments: false,
            },
            // chart.js will fail if sources are mangled
            /*
            mangle: {
                properties: {
                    regex: /^_/,
                },
            },
            */
        }
    })

    .enableEslintPlugin()
;

module.exports = Encore.getWebpackConfig();
