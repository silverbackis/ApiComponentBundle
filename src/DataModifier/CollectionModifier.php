<?php

namespace Silverback\ApiComponentBundle\DataModifier;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\PathResolver\OperationPathResolverInterface;
use Psr\Container\ContainerInterface;
use Silverback\ApiComponentBundle\Entity\Component\Collection\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CollectionModifier extends AbstractModifier
{
    private $resourceMetadataFactory;
    private $operationPathResolver;
    private $itemNormalizer;
    private $requestStack;

    public function __construct(
        ContainerInterface $container,
        ResourceMetadataFactoryInterface $resourceMetadataFactory,
        OperationPathResolverInterface $operationPathResolver,
        NormalizerInterface $itemNormalizer,
        RequestStack $requestStack
    ) {
        parent::__construct($container);
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->operationPathResolver = $operationPathResolver;
        $this->itemNormalizer = $itemNormalizer;
        $this->requestStack = $requestStack;
    }

    /**
     * @param Collection $collectionEntity
     * @param array $context
     * @param null|string $format
     * @return object|void
     */
    public function process($collectionEntity, array $context = array(), ?string $format = null)
    {
        $resourceMetadata = $this->resourceMetadataFactory->create($collectionEntity->getResource());
        $requestUri = null;

        $collectionOperations = $resourceMetadata->getCollectionOperations();
        if ($collectionOperations && ($shortName = $resourceMetadata->getShortName())) {
            $collectionOperations = array_change_key_case($collectionOperations, CASE_LOWER);
            $baseRoute = trim(trim($resourceMetadata->getAttribute('route_prefix', '')), '/');
            $methods = ['post', 'get'];
            foreach ($methods as $method) {
                if (array_key_exists($method, $collectionOperations)) {
                    $path = $baseRoute . $this->operationPathResolver->resolveOperationPath(
                            $shortName,
                            $collectionOperations[$method],
                            OperationType::COLLECTION,
                            $method
                    );
                    $finalPath = preg_replace('/{_format}$/', $format, $path);
                    $collectionEntity->addCollectionRoute(
                        $method,
                        $finalPath
                    );
                    if ($method === 'get') {
                        $requestUri = $finalPath;
                    }
                }
            }
        }

        /** @var ContextAwareCollectionDataProviderInterface $dataProvider */
        $dataProvider = $this->container->get(ContextAwareCollectionDataProviderInterface::class);
        $isPaginated = (bool) $collectionEntity->getPerPage();
        if (!$isPaginated) {
            $dataProviderContext = [];
        } else {
            $dataProviderContext = [
                'filters' => [
                    'pagination' => $isPaginated,
                    'itemsPerPage' => $collectionEntity->getPerPage(),
                    '_page' => 1
                ]
            ];
            if ($collectionEntity->getPerPage() !== null && ($request = $this->requestStack->getCurrentRequest())) {
                $request->attributes->set('_api_pagination', [
                    'itemsPerPage' => $collectionEntity->getPerPage(),
                    'pagination' => 'true'
                ]);
            }
        }

        /** @var Paginator $collection */
        $collection = $dataProvider->getCollection($collectionEntity->getResource(), Request::METHOD_GET, $dataProviderContext);

        $forcedContext = [
            'resource_class' => $collectionEntity->getResource(),
            'request_uri' => $requestUri,
            'jsonld_has_context' => false,
            'api_sub_level' => null
        ];
        $mergedContext = array_merge($context, $forcedContext);
        $normalizedCollection = $this->itemNormalizer->normalize(
            $collection,
            $format,
            $mergedContext
        );
        if (\is_array($normalizedCollection)) {
            $collectionEntity->setCollection($normalizedCollection);
        }
    }

    public function supportsData($data): bool
    {
        return $data instanceof Collection;
    }

    public static function getSubscribedServices(): array
    {
        return [
            '?' . ContextAwareCollectionDataProviderInterface::class,
            RequestStack::class
        ];
    }
}
