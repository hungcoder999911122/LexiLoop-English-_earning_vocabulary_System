package com.example.englishvocab.repository;

import com.example.englishvocab.entity.VocabularyExample;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface VocabularyExampleRepository extends JpaRepository<VocabularyExample, Integer> {
    List<VocabularyExample> findBySense_VocabularySenseID(Integer senseId);
}
