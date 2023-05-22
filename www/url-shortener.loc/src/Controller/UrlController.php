<?php

namespace App\Controller;

use App\Entity\Url;
use App\Repository\UrlRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Url as UrlValidator;

class UrlController extends AbstractController
{
    /**
     * @Route("/encode-url", name="encode_url")
     */
    public function encodeUrl(Request $request): JsonResponse
    {
        $urlRequest = $request->get('url');
        $urlRepository = $this->getDoctrine()->getRepository(Url::class);
        $url = $urlRepository->findOneByUrl($urlRequest);
        if(!$url || time() > $url->getLive())
        {
            $urlRequest = filter_var($urlRequest, FILTER_VALIDATE_URL);
            if(!$urlRequest) {
                return $this->json([
                    'error' => 'Not valid url'
                ]);
            }
            $url = new Url();
            $url->setUrl($request->get('url'));
            $url->setLive(time()+60*60);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($url);
            $entityManager->flush();
        }

        return $this->json([
            'hash' => $url->getHash(),
            'live' => $url->getLive()
        ]);
    }

    /**
     * @Route("/decode-url", name="decode_url")
     */
    public function decodeUrl(Request $request): JsonResponse
    {
        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->getDoctrine()->getRepository(Url::class);
        $hash = $request->get('hash');
        if(!$hash) {
            return $this->json([
                'error' => 'Non-specified hash.'
            ]);
        }
        $url = $urlRepository->findOneByHash($hash);
        if (empty ($url)) {
            return $this->json([
                'error' => 'Non-existent hash.'
            ]);
        }
        if (time() > $url->getLive()) {
            return $this->json([
                'error' => 'Live has expired'
            ]);
        }
        return $this->json([
            'url' => $url->getUrl()
        ]);
    }

    /**
     * @Route("/go-url", name="go_url")
     */
    public function goUrl(Request $request)
    {
        /** @var UrlRepository $urlRepository */
        $urlRepository = $this->getDoctrine()->getRepository(Url::class);
        $hash = $request->get('hash');
        if(!$hash) {
            return $this->json([
                'error' => 'Non-specified hash.'
            ]);
        }
        $url = $urlRepository->findOneByHash($hash);
        if (empty ($url)) {
            return $this->json([
                'error' => 'Non-existent hash.'
            ]);
        }
        if (time() > $url->getLive()) {
            return $this->json([
                'error' => 'Live has expired'
            ]);
        }
        return $this->redirect($url->getUrl());
    }
}
