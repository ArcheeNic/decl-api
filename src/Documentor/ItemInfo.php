<?php namespace DeclApi\Documentor;

use DeclApi\Core\ObjectClass;

abstract class ItemInfo
{
    /**
     * @var string|null $title название
     */
    protected $title;

    /**
     * @var array|null $description описание
     */
    protected $description;

    /**
     * @var array|null
     */
    protected $tag;

    /**
     * @var string $classname Имя класса
     */
    protected $classname;

    /**
     * ItemInfo constructor.
     *
     * @param string $classname
     *
     * @throws \ReflectionException
     */
    public function __construct(string $classname)
    {
        $this->classname = $classname;
        $this->getInfo();

        $this->analyse();
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): array
    {
        return $this->description;
    }

    /**
     * @return array|null
     */
    public function getTag()
    {
        return $this->tag;
    }


    /**
     * @return string
     */
    public function getClassname(): string
    {
        return $this->classname;
    }

    /**
     * @throws \ReflectionException
     */
    protected function getInfo()
    {
        $classname         = $this->classname;
        $reflection        = new \ReflectionClass($classname);
        $docRaw            = ($reflection->getDocComment());
        $doc               = $this->parseDoc($docRaw);
        $this->title       = $doc['title'];
        $this->description = $doc['description'];
        $this->tag         = (isset($doc['tags']) && isset($doc['tags']['tag'])) ? $doc['tags']['tag'] : [];
        $this->tag         = array_map('trim', $this->tag);
    }

    protected function parseDoc($content)
    {
        $array = [
            'title'       => '',
            'description' => [],
            'tags'        => []
        ];
        if (preg_match_all('!\* (.*?)$!umi', $content, $match)) {
            $lastTag = '';
            foreach ($match[1] as $k => $v) {
                if ($v) {
                    $tagInfo = static::parseDocTag($v);
                    if ($tagInfo) {
                        $array['tags'][$tagInfo['name']] = [$tagInfo['content']];
                        $lastTag                         = $tagInfo['name'];
                        continue;
                    } elseif ($lastTag) {
                        $array['tags'][$lastTag][] = $v;
                        continue;
                    }
                    if (!$array['title'] && !$lastTag) {
                        $array['title'] = $v;
                        continue;
                    }
                    if (!$lastTag && $array['title']) {
                        $array['description'][] = $v;
                        continue;
                    }
                }
            }
        }
        return $array;
    }

    protected function parseDocTag($content)
    {
        $info = null;
        if (preg_match('!^@(.*?)[^\S](.*?|.*?)$!umi', trim($content), $match)) {
            $info = [
                'name'    => $match[1],
                'content' => $match[2],
            ];
        } elseif (preg_match('!^@(.*?)$!umi', trim($content), $match)) {
            $info = [
                'name'    => $match[1],
                'content' => '',
            ];
        }

        return $info;
    }

    abstract function analyse();


}