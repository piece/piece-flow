<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2012 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Piece_Flow
 * @copyright  2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 0.1.0
 */

namespace Piece\Flow\PageFlow;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @package    Piece_Flow
 * @copyright  2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Definition17Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('definition17')
            ->children()
                ->scalarNode('name')->defaultNull()->cannotBeEmpty()->end()
                ->scalarNode('firstState')->isRequired()->cannotBeEmpty()->end()
                ->arrayNode('viewState')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('view')->isRequired()->cannotBeEmpty()->end()
                            ->arrayNode('transition')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('event')->isRequired()->cannotBeEmpty()->end()
                                        ->scalarNode('nextState')->isRequired()->cannotBeEmpty()->end()
                                        ->arrayNode('action')
                                            ->children()
                                                ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                                ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('guard')
                                            ->children()
                                                ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                                ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('entry')
                                ->children()
                                    ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                    ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                            ->arrayNode('exit')
                                ->children()
                                    ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                    ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                            ->arrayNode('activity')
                                ->children()
                                    ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                    ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('actionState')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                            ->arrayNode('transition')
                                ->isRequired()
                                ->requiresAtLeastOneElement()
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('event')->isRequired()->cannotBeEmpty()->end()
                                        ->scalarNode('nextState')->isRequired()->cannotBeEmpty()->end()
                                        ->arrayNode('action')
                                            ->children()
                                                ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                                ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('guard')
                                            ->children()
                                                ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                                ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('entry')
                                ->children()
                                    ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                    ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                            ->arrayNode('exit')
                                ->children()
                                    ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                    ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                            ->arrayNode('activity')
                                ->children()
                                    ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                    ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('lastState')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('view')->isRequired()->cannotBeEmpty()->end()
                        ->arrayNode('entry')
                            ->children()
                                ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                        ->arrayNode('exit')
                            ->children()
                                ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                        ->arrayNode('activity')
                            ->children()
                                ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                                ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('initial')
                    ->children()
                        ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                        ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('final')
                    ->children()
                        ->scalarNode('class')->defaultNull()->cannotBeEmpty()->end()
                        ->scalarNode('method')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
